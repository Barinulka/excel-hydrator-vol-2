package main

import (
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"sort"
	"strconv"
	"strings"
	"time"
	"unicode/utf8"

	"github.com/xuri/excelize/v2"
)

const (
	defaultTemplateDir = "/app/templates"
	defaultOutputDir   = "/app/output"
	defaultPort        = "8080"

	cellStyleDefault    = "default"
	cellStyleInput      = "input"
	cellStyleCalculated = "calculated"
	cellStyleReference  = "reference"
	cellStyleTechnical  = "technical"
	cellStyleHint       = "hint"
)

type Request struct {
	Template string               `json:"template"`
	Data     map[string]SheetData `json:"data"`
}

type SheetData map[string]any

type CellPayload struct {
	Value          any                   `json:"value"`
	Formula        string                `json:"formula"`
	NumFormat      string                `json:"num_format"`
	CellStyle      string                `json:"cell_style"`
	DataValidation *DataValidationConfig `json:"data_validation"`
}

type DataValidationConfig struct {
	Type    string   `json:"type"`
	Options []string `json:"options"`
}

type App struct {
	templateDir string
	outputDir   string
}

func main() {
	app := &App{
		templateDir: getEnv("TEMPLATE_DIR", defaultTemplateDir),
		outputDir:   getEnv("OUTPUT_DIR", defaultOutputDir),
	}

	if err := os.MkdirAll(app.outputDir, 0o755); err != nil {
		log.Fatalf("failed to create output directory: %v", err)
	}

	http.HandleFunc("/health", app.healthHandler)
	http.HandleFunc("/generate", app.generateHandler)

	port := getEnv("PORT", defaultPort)
	log.Printf("excel-hydrator listening on :%s", port)
	log.Fatal(http.ListenAndServe(":"+port, nil))
}

func (app *App) healthHandler(w http.ResponseWriter, _ *http.Request) {
	writeJSON(w, http.StatusOK, map[string]string{
		"status": "ok",
	})
}

func (app *App) generateHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		writeJSONError(w, http.StatusMethodNotAllowed, "Only POST allowed")
		return
	}

	var req Request
	if err := json.NewDecoder(r.Body).Decode(&req); err != nil {
		writeJSONError(w, http.StatusBadRequest, "Invalid JSON")
		return
	}

	if len(req.Data) == 0 {
		writeJSONError(w, http.StatusBadRequest, "Data is required")
		return
	}

	file, templateLoaded, err := app.openWorkbook(req.Template)
	if err != nil {
		writeJSONError(w, http.StatusNotFound, err.Error())
		return
	}
	defer file.Close()

	// New workbook created without template contains a default "Sheet1".
	// Replace it with first business sheet name to avoid extra empty tab.
	if !templateLoaded {
		normalizeDefaultSheet(file, req.Data)
	}

	styleCache := map[string]int{}

	for sheetName, sheetData := range req.Data {
		if strings.TrimSpace(sheetName) == "" {
			writeJSONError(w, http.StatusBadRequest, "Sheet name must be a non-empty string")
			return
		}

		ensureSheet(file, sheetName)
		if err := configureSheetView(file, sheetName); err != nil {
			writeJSONError(w, http.StatusInternalServerError, "Failed to configure sheet view")
			return
		}

		for cellRef, rawValue := range sheetData {
			if err := applyCellValue(file, sheetName, cellRef, rawValue, styleCache); err != nil {
				writeJSONError(w, http.StatusBadRequest, err.Error())
				return
			}
		}

		if err := autoFitSheetColumns(file, sheetName, sheetData); err != nil {
			writeJSONError(w, http.StatusInternalServerError, "Failed to configure column widths")
			return
		}
	}

	if err := enableFormulaRecalculation(file); err != nil {
		log.Printf("SetCalcProps error: %v", err)
		writeJSONError(w, http.StatusInternalServerError, "Failed to configure calculation")
		return
	}

	filename := "hydrated_" + time.Now().Format("20060102_150405_000000") + ".xlsx"
	outputPath := filepath.Join(app.outputDir, filename)
	if err := file.SaveAs(outputPath); err != nil {
		log.Printf("SaveAs error: %v", err)
		writeJSONError(w, http.StatusInternalServerError, "Failed to save file")
		return
	}

	writeJSON(w, http.StatusOK, map[string]string{
		"filename": filename,
	})
}

func (app *App) openWorkbook(template string) (*excelize.File, bool, error) {
	template = strings.TrimSpace(template)
	if template == "" {
		return excelize.NewFile(), false, nil
	}

	templatePath := filepath.Join(app.templateDir, filepath.Base(template))
	if _, err := os.Stat(templatePath); errors.Is(err, os.ErrNotExist) {
		// Fallback to empty workbook if template is absent.
		return excelize.NewFile(), false, nil
	}

	file, err := excelize.OpenFile(templatePath)
	if err != nil {
		log.Printf("OpenFile error: %v", err)
		return nil, false, fmt.Errorf("failed to open template")
	}

	return file, true, nil
}

func ensureSheet(file *excelize.File, sheetName string) {
	index, err := file.GetSheetIndex(sheetName)
	if err != nil || index == -1 {
		file.NewSheet(sheetName)
	}
}

func normalizeDefaultSheet(file *excelize.File, data map[string]SheetData) {
	sheets := file.GetSheetList()
	if len(sheets) != 1 || sheets[0] != "Sheet1" {
		return
	}

	if _, hasDefault := data["Sheet1"]; hasDefault {
		return
	}

	target := firstSheetName(data)
	if target == "" {
		return
	}

	if err := file.SetSheetName("Sheet1", target); err != nil {
		log.Printf("SetSheetName error: %v", err)
	}
}

func firstSheetName(data map[string]SheetData) string {
	if len(data) == 0 {
		return ""
	}

	if _, ok := data["Входные данные"]; ok {
		return "Входные данные"
	}

	names := make([]string, 0, len(data))
	for name := range data {
		trimmed := strings.TrimSpace(name)
		if trimmed != "" {
			names = append(names, trimmed)
		}
	}

	if len(names) == 0 {
		return ""
	}

	sort.Strings(names)

	return names[0]
}

func applyCellValue(file *excelize.File, sheetName, cellRef string, rawValue any, styleCache map[string]int) error {
	payload, hasPayload := parseCellPayload(rawValue)
	cellStyle := cellStyleDefault
	if hasPayload && strings.TrimSpace(payload.CellStyle) != "" {
		cellStyle = strings.TrimSpace(payload.CellStyle)
	}

	if hasPayload && payload.Formula != "" {
		if err := file.SetCellFormula(sheetName, cellRef, payload.Formula); err != nil {
			return fmt.Errorf("invalid formula for %s!%s", sheetName, cellRef)
		}

		// Optional fallback/static value for viewers that don't calculate formulas.
		if payload.Value != nil {
			if err := file.SetCellValue(sheetName, cellRef, payload.Value); err != nil {
				return fmt.Errorf("invalid value for %s!%s", sheetName, cellRef)
			}
		}

		if payload.DataValidation != nil {
			if err := applyCellDataValidation(file, sheetName, cellRef, payload.DataValidation); err != nil {
				return fmt.Errorf("failed to apply data validation for %s!%s", sheetName, cellRef)
			}
		}

		if err := applyCellStyle(file, sheetName, cellRef, cellStyle, payload.NumFormat, styleCache); err != nil {
			return fmt.Errorf("failed to apply style for %s!%s", sheetName, cellRef)
		}

		return nil
	}

	value := rawValue
	if hasPayload {
		value = payload.Value
	}

	if err := file.SetCellValue(sheetName, cellRef, value); err != nil {
		return fmt.Errorf("invalid cell address: %s!%s", sheetName, cellRef)
	}

	if hasPayload && payload.DataValidation != nil {
		if err := applyCellDataValidation(file, sheetName, cellRef, payload.DataValidation); err != nil {
			return fmt.Errorf("failed to apply data validation for %s!%s", sheetName, cellRef)
		}
	}

	numFormat := ""
	if hasPayload {
		numFormat = payload.NumFormat
	}
	if err := applyCellStyle(file, sheetName, cellRef, cellStyle, numFormat, styleCache); err != nil {
		return fmt.Errorf("failed to apply style for %s!%s", sheetName, cellRef)
	}

	return nil
}

func parseCellPayload(raw any) (CellPayload, bool) {
	rawMap, ok := raw.(map[string]any)
	if !ok {
		return CellPayload{}, false
	}

	payload := CellPayload{
		Value: rawMap["value"],
	}
	if formula, ok := rawMap["formula"].(string); ok {
		payload.Formula = formula
	}
	if numFormat, ok := rawMap["num_format"].(string); ok {
		payload.NumFormat = strings.TrimSpace(numFormat)
	}
	if cellStyle, ok := rawMap["cell_style"].(string); ok {
		payload.CellStyle = strings.TrimSpace(cellStyle)
	}
	if dataValidation := parseDataValidationConfig(rawMap["data_validation"]); dataValidation != nil {
		payload.DataValidation = dataValidation
	}

	return payload, true
}

func parseDataValidationConfig(raw any) *DataValidationConfig {
	rawMap, ok := raw.(map[string]any)
	if !ok {
		return nil
	}

	config := &DataValidationConfig{}
	if validationType, ok := rawMap["type"].(string); ok {
		config.Type = strings.TrimSpace(validationType)
	}

	rawOptions, ok := rawMap["options"].([]any)
	if ok {
		for _, option := range rawOptions {
			if value, ok := option.(string); ok {
				trimmed := strings.TrimSpace(value)
				if trimmed != "" {
					config.Options = append(config.Options, trimmed)
				}
			}
		}
	}

	if config.Type == "" {
		return nil
	}

	return config
}

func configureSheetView(file *excelize.File, sheetName string) error {
	return file.SetSheetView(sheetName, 0, &excelize.ViewOptions{
		ShowGridLines: boolPtr(false),
	})
}

func autoFitSheetColumns(file *excelize.File, sheetName string, sheetData SheetData) error {
	widths := map[int]float64{}

	for cellRef, rawValue := range sheetData {
		columnIndex, _, err := excelize.CellNameToCoordinates(cellRef)
		if err != nil {
			return err
		}

		width := estimateCellWidth(rawValue)
		if currentWidth, ok := widths[columnIndex]; !ok || width > currentWidth {
			widths[columnIndex] = width
		}
	}

	columns := make([]int, 0, len(widths))
	for columnIndex := range widths {
		columns = append(columns, columnIndex)
	}
	sort.Ints(columns)

	for _, columnIndex := range columns {
		columnName, err := excelize.ColumnNumberToName(columnIndex)
		if err != nil {
			return err
		}

		if err := file.SetColWidth(sheetName, columnName, columnName, clampColumnWidth(widths[columnIndex])); err != nil {
			return err
		}
	}

	return nil
}

func estimateCellWidth(rawValue any) float64 {
	payload, hasPayload := parseCellPayload(rawValue)
	if hasPayload {
		if strings.TrimSpace(payload.NumFormat) != "" {
			return float64(maxInt(12, utf8.RuneCountInString(payload.NumFormat)))
		}

		if payload.DataValidation != nil && len(payload.DataValidation.Options) > 0 {
			longest := utf8.RuneCountInString(fmt.Sprint(payload.Value))
			for _, option := range payload.DataValidation.Options {
				longest = maxInt(longest, utf8.RuneCountInString(option))
			}

			return float64(longest + 3)
		}

		if payload.Value != nil {
			return estimateScalarWidth(payload.Value)
		}

		if payload.Formula != "" {
			return 6
		}
	}

	return estimateScalarWidth(rawValue)
}

func estimateScalarWidth(value any) float64 {
	switch typedValue := value.(type) {
	case nil:
		return 6
	case string:
		return float64(longestLineWidth(typedValue) + 2)
	case int:
		return float64(len(strconv.Itoa(typedValue)) + 2)
	case int8, int16, int32, int64, uint, uint8, uint16, uint32, uint64, float32, float64, bool:
		return float64(utf8.RuneCountInString(fmt.Sprint(typedValue)) + 2)
	default:
		return float64(utf8.RuneCountInString(fmt.Sprint(typedValue)) + 2)
	}
}

func longestLineWidth(value string) int {
	lines := strings.Split(value, "\n")
	longest := 0
	for _, line := range lines {
		longest = maxInt(longest, utf8.RuneCountInString(line))
	}

	return longest
}

func clampColumnWidth(width float64) float64 {
	const (
		minWidth = 6.0
		maxWidth = 48.0
	)

	if width < minWidth {
		return minWidth
	}

	if width > maxWidth {
		return maxWidth
	}

	return width
}

func applyCellStyle(file *excelize.File, sheetName, cellRef, cellStyle, numFormat string, styleCache map[string]int) error {
	styleID, err := getOrCreateCellStyle(file, cellStyle, numFormat, styleCache)
	if err != nil {
		return err
	}

	return file.SetCellStyle(sheetName, cellRef, cellRef, styleID)
}

func getOrCreateCellStyle(file *excelize.File, cellStyle, numFormat string, styleCache map[string]int) (int, error) {
	cacheKey := strings.TrimSpace(cellStyle) + "|" + strings.TrimSpace(numFormat)
	if styleID, ok := styleCache[cacheKey]; ok {
		return styleID, nil
	}

	styleDef := buildStyleDefinition(cellStyle, numFormat)
	styleID, err := file.NewStyle(styleDef)
	if err != nil {
		return 0, err
	}

	styleCache[cacheKey] = styleID

	return styleID, nil
}

func buildStyleDefinition(cellStyle, numFormat string) *excelize.Style {
	fontColor := "000000"
	fill := excelize.Fill{}
	borders := []excelize.Border{}
	italic := false

	switch cellStyle {
	case cellStyleInput:
		fill = excelize.Fill{
			Type:    "pattern",
			Pattern: 1,
			Color:   []string{"FEF9B6"},
		}
		borders = []excelize.Border{
			{Type: "left", Color: "7F7F7F", Style: 1},
			{Type: "top", Color: "7F7F7F", Style: 1},
			{Type: "right", Color: "7F7F7F", Style: 1},
			{Type: "bottom", Color: "7F7F7F", Style: 1},
		}
	case cellStyleCalculated:
		fontColor = "31681F"
		borders = []excelize.Border{
			{Type: "left", Color: "7F7F7F", Style: 1},
			{Type: "top", Color: "7F7F7F", Style: 1},
			{Type: "right", Color: "7F7F7F", Style: 1},
			{Type: "bottom", Color: "7F7F7F", Style: 1},
		}
	case cellStyleReference:
		fontColor = "0070C0"
		borders = []excelize.Border{
			{Type: "left", Color: "7F7F7F", Style: 1},
			{Type: "top", Color: "7F7F7F", Style: 1},
			{Type: "right", Color: "7F7F7F", Style: 1},
			{Type: "bottom", Color: "7F7F7F", Style: 1},
		}
	case cellStyleTechnical:
		fill = excelize.Fill{
			Type:    "pattern",
			Pattern: 1,
			Color:   []string{"D9D9D9"},
		}
	case cellStyleHint:
		italic = true
	}

	style := &excelize.Style{
		Font: &excelize.Font{
			Family: "Arial Narrow",
			Size:   12,
			Color:  fontColor,
			Italic: italic,
		},
		Fill:   fill,
		Border: borders,
	}

	if strings.TrimSpace(numFormat) != "" {
		format := strings.TrimSpace(numFormat)
		style.CustomNumFmt = &format
	}

	return style
}

func applyCellDataValidation(file *excelize.File, sheetName, cellRef string, config *DataValidationConfig) error {
	switch config.Type {
	case "list":
		if len(config.Options) == 0 {
			return nil
		}

		dv := excelize.NewDataValidation(true)
		dv.SetSqref(cellRef)
		if err := dv.SetDropList(config.Options); err != nil {
			return err
		}

		return file.AddDataValidation(sheetName, dv)
	default:
		return nil
	}
}

func enableFormulaRecalculation(file *excelize.File) error {
	calcMode := "auto"
	fullCalcOnLoad := true
	forceFullCalc := true

	return file.SetCalcProps(&excelize.CalcPropsOptions{
		CalcMode:       &calcMode,
		FullCalcOnLoad: &fullCalcOnLoad,
		ForceFullCalc:  &forceFullCalc,
	})
}

func writeJSONError(w http.ResponseWriter, status int, message string) {
	writeJSON(w, status, map[string]string{
		"message": message,
	})
}

func writeJSON(w http.ResponseWriter, status int, payload any) {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(status)
	_ = json.NewEncoder(w).Encode(payload)
}

func getEnv(key, fallback string) string {
	value := strings.TrimSpace(os.Getenv(key))
	if value == "" {
		return fallback
	}

	return value
}

func boolPtr(value bool) *bool {
	return &value
}

func maxInt(left, right int) int {
	if left > right {
		return left
	}

	return right
}
