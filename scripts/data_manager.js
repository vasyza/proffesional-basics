export async function sendData(formData, url) {

    let request = await fetch(url, {
        method: 'POST',
        body: formData
    });

    let response = await request.json();
    return response;
}

export async function getData(formData, url) {

    let request = await fetch(url, {
        method: 'POST',
        body: formData
    });

    let response = await request.json();
    return response;
}