

// окно 1 этапа: выборка ПВК
const choose_piqs_window = document.querySelector('#choose_piqs');

// кнопка чтоб открыть окно 1 этапа
const windowButtons = document.querySelectorAll('#rate-button');

// и закрыть (оба окна выборки и оценки)
const closeBtns = document.querySelectorAll('.close-rate-windows');

// окно 2 этапа: рейтинг ПВК
const rate_piqs_window = document.querySelector('#rate_piqs');
// кнопка, чтоб открыть окно 2 этапа (выбрать ПВК и перейти ко второму)
const choosePiqsButton = document.querySelector('#rate-button1');

// итоговая кнопка: оценить профессию (конец 2 этапа)
const setRatingButton = document.querySelector('#rate-button2');

// настройка хранилища в сесссии
sessionStorage.setItem("rateState", 0);
sessionStorage.setItem("profession_id", 0);
sessionStorage.setItem("expert_id", 0);
var piqRating = new Map();
var piqs = [];

// функция чтоб очистить хранилище сессии
function clearRate() {
    sessionStorage.setItem("profession_id", 0);
    sessionStorage.setItem("expert_id", 0);
    sessionStorage.setItem("rateState", 0);
    piqRating = new Map();
    piqs = [];
}

//фукнция для обновления в сессии списка пвк
function refreshPiqs() {
    sessionStorage.setItem("ratePiqs", piqs);
}
refreshPiqs();

//также для рейтинга пвк
function refreshRating() {
    sessionStorage.setItem("rateRating", piqRating);
}
refreshRating();


// добавить слушатели к 1 этапу (на клик делать функцию)
Array.from(windowButtons).forEach(windowButton => {
    windowButton.addEventListener('click', openChoosePiqsWindow.bind(null, windowButton), true);

});
// choose_piqs_window.addEventListener('click', outsideClick1);

// // функция закрытия окон для 1 и 2 этапов
// Array.from(closeBtns).forEach(closeBtn => {
//     closeBtn.addEventListener('click', closeWindows);
// });
// function closeWindows() {
//     choose_piqs_window.style.display = 'none';
//     rate_piqs_window.style.display = 'none';
//     if (showRatingWindow != null) {
//         showRatingWindow.style.display = 'none';
//     }
//     clearRate();
// }

// фукнции 1 этапа
function openChoosePiqsWindow(windowButton) {
    console.log("123");
    // console.log(windowButton.name.split('_'));
    var splitted = windowButton.name.split('_');
    // console.log("нажато " + windowButton.name);
    var profession_id = splitted[0];
    sessionStorage.setItem("profession_id", profession_id);
    var expert_id = splitted[1];
    sessionStorage.setItem('expert_id', expert_id);
    // console.log(prof_id, expert_id);
    choose_piqs_window.style.display = 'block';
    sessionStorage.setItem('rateState', 1);
}
function outsideClick1(e) {
    if (e.target == choose_piqs_window) {
        choose_piqs_window.style.display = 'none';
        clearRate();
    }
}

// слушатели ко 2 этапу
choosePiqsButton.addEventListener('click', openRatePiqsWindow);
rate_piqs_window.addEventListener('click', outsideClick2);

function openRatePiqsWindow() {
    var checks = document.getElementsByClassName('piq_checkbox');
    var chosenPiqs = [];

    for (var i = 0; i < checks.length; i++) {
        var piq_id = checks[i].id;
        if (document.getElementById(piq_id).checked === true) {
            chosenPiqs.push(piq_id.substring(4, piq_id.length));
        }
    }
    if (chosenPiqs.length >= 5 && chosenPiqs.length <= 10) {
        // всё хорошо
        piqs = chosenPiqs;
        for (var i = 0; i < piqs; i++) {
            piqRating.set(piqs[i], 10 - i);
        }

        choose_piqs_window.style.display = 'none';
        rate_piqs_window.style.display = 'block';

        // получить список мест в рейтинге пвк
        var items_css = document.querySelectorAll('.piq_control_item');
        var items_el = document.getElementsByClassName('piq_control_item');
        for (var i = 0; i < items_css.length; i++) {
            var current_piq_id = items_el[i].id;
            current_piq_id = current_piq_id.substring(17, current_piq_id.length);
            if (piqs.includes(current_piq_id)) {
                items_css[i].style.display = 'block';
            } else {
                items_css[i].style.display = 'none';
            }
        }
        // console.log(items_css);
        // console.log(items_el);
        // console.log(piqs);

        sessionStorage.setItem('rateState', 2);

        refreshPiqs();
        refreshRating();
    } else {
        alert("Количество выбранных ПВК должно быть от 5 до 10");
    }
    console.log(chosenPiqs);
    console.log(checks);
}
function outsideClick2(e) {
    if (e.target === rate_piqs_window) {
        rate_piqs_window.style.display = 'none';
        clearRate();
    }
}

// слушатель кнопки конца 2 этапа:
setRatingButton.addEventListener('click', submitRating)
function submitRating() {
    var inputValues = document.getElementsByClassName('piq_imp');
    var ratingMap = new Map();
    for (var i = 0; i < inputValues.length; i++) {
        var input = inputValues[i].value;
        var piq_id = inputValues[i].getAttribute('piq_id');

        if (input < 0 || input > 10 || input % 1 != 0) {
            alert("Числа должны быть целые, от 0 до 10 включительно");
            console.log(ratingMap);
            return;
        } else {
            if (piqs.includes(piq_id)) {
                ratingMap.set(piq_id, input);
            }
        }
    }
    if (hasDuplicates(Array.from(ratingMap.values()))) {
        alert("Числа должны быть различны");
    } else {
        // всё хорошо здесь, только осталось послать запрос:
        piqRating = ratingMap;
        refreshRating();
        refreshPiqs();
        sessionStorage.setItem("rateState", 3);

        // производство запросов в бд
        var proffesionId = sessionStorage.getItem('profession_id');
        var expertId = sessionStorage.getItem('expert_id');
        var queries = []
        for (let [pidId, importance] of piqRating) {
            console.log(pidId, importance);
            // piq_id -> importance
            queries.push("INSERT INTO `ratings` (`profession_id`, `piq_id`, `priority`, `expert_id`, `date`) VALUES ('" + proffesionId + "', '" + pidId + "', '" + importance + "', '" + expertId + "', current_timestamp());");
        }

        // все запросы в виде массива и в json
        var jsonString = JSON.stringify(queries);
        jsonString = encodeURIComponent(jsonString);

        // объект xmlhttprequest
        var xhr = new XMLHttpRequest();

        // открыть подключение, метод post и потом идти в rate.php
        xhr.open('POST', 'backend/rate.php');

        // задать кодировку
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // обработка при успешном посылании запроса
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                sessionStorage.setItem('rateState', 0);
                alert("Оценка учтена!");
                closeWindows();
                window.location.reload(true);
            }
        }

        // отправляем запрос
        console.log(jsonString);
        xhr.send("queries=" + jsonString);
    }
}

// вспомогательная функция, проверяющая уникальность значений массива
function hasDuplicates(array) {
    return (new Set(array)).size !== array.length;
}

// показать профессии
// const showRatingWindow = document.getElementById("show_rating");
let showRatingWindow = null;

const showRatingWindows = document.querySelectorAll(".show_rating_window");
Array.from(showRatingWindows).forEach((showRatingWindow) => {
    showRatingWindow.addEventListener('click', (e) => {
        if (e.target == showRatingWindow) {
            showRatingWindow.style.display = 'none';
        }
    });
});
const showRatingButtons = document.querySelectorAll("#view-rating-button");
Array.from(showRatingButtons).forEach((showRatingButton) => {
    showRatingButton.addEventListener('click', showRate.bind(null, showRatingButton));
});
function showRate(showRatingButton) {
    var splitted = showRatingButton.name.split('_');
    var profession_id = splitted[0];
    var expert_id = splitted[1];
    showRatingWindow = document.querySelector(`#show_rating_${profession_id}_${expert_id}`);
    showRatingWindow.style.display = 'block';
    // console.log(profession_id, expert_id, showRatingWindow);

    // const data = {
    //     profId: profession_id,
    //     expertId: expert_id
    // };
    // var response = sendData('../backend/get_ratings.php', JSON.stringify(data))
    // .catch((err) => {
    //     console.log(err);
    // });

    // response.then((data) => {console.log(data);});
}
