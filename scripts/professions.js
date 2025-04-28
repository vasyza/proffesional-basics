const pvcButtons = document.querySelectorAll('.pvc-button');

pvcButtons.forEach((button, index) => {
    button.addEventListener('click', () => {
        const pvcLists = document.querySelectorAll('.pvc-list');
        pvcLists.forEach((list) => {
            list.style.display = 'none';
        });
        pvcLists[index].style.display = 'flex';
    });
});

const myPiqsButtons = document.querySelectorAll('.my-piqs');
const myPiqsWindow = document.getElementById("my-piqs-window");
const myPiqsClose = document.getElementById("close_my_piqs_window");

myPiqsButtons.forEach((button, index) => {
    button.addEventListener('click', () => {
        let profId = button.getAttribute("profession_id");
        myPiqsWindow.style.display = 'block';
        myPiqsClose.addEventListener('click', closeMyPiqsWindow);
        myPiqsWindow.addEventListener('click', outsideClickMyPiqsWindow);

    })
});

function closeMyPiqsWindow() {
    myPiqsWindow.style.display = 'none'
    myPiqsClose.removeEventListener('click', closeMyPiqsWindow);
    myPiqsWindow.removeEventListener('click', outsideClickMyPiqsWindow);
}

function outsideClickMyPiqsWindow(e) {
    if (e.target === myPiqsWindow) {
        myPiqsWindow.style.display = 'none';
        myPiqsClose.removeEventListener('click', closeMyPiqsWindow);
        myPiqsWindow.removeEventListener('click', outsideClickMyPiqsWindow);
    }
}