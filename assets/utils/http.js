export async function fetchJson(url, options = {}) {
    const response = await fetch(url, options);

    let data = {};
    try {
        data = await response.json();
    } catch (error) {
        data = {};
    }

    return { response, data };
}
