import {sendData, getData} from "./data_manager.js";

document.addEventListener('DOMContentLoaded', () => {
    const pvkData = [
        {id: 253, name: 'Абстрактность'},
        {id: 301, name: 'Объем внимания'},
        {id: 245, name: 'Аналитичность'},
        {id: 246, name: 'Синтетичность'},
        {id: 282, name: 'Умственная работоспособность'},
        {id: 251, name: 'Предметность'},
        {id: 240, name: 'Способность к пространственному воображению'},
        {id: 241, name: 'Способность к воссозданию образа по словесному описанию'},
        {id: 244, name: 'Способность к образному представлению предметов, процессов и явлений'},
        {id: 249, name: 'Креативность'},
        {id: 215, name: 'Способность аргументировано отстаивать свое мнение'},
        {id: 254, name: 'Вербальность'},
        {id: 260, name: 'Зрительная долговременная память на слова и фразы'}
    ];

    const stats = {
        'Простые визуальные сигналы': 'accuracy',
        'Простые визуальные сигналы - Ср. время ус. Попыток': 'reaction_time',
        'Простые звуковые сигналы': 'accuracy',
        'Простые звуковые сигналы - Ср. время ус. Попыток': 'reaction_time',
        'Сложные цветные сигналы': 'accuracy',
        'Сложные цветные сигналы - Ошибки': 'mistakes',
        'Сложные цветные сигналы - Ср. время ус. Попыток': 'reaction_time',
        'Сл. Цифровые визуальные сигналы': 'accuracy',
        'Сл. Цифровые визуальные сигналы - Ср. время ус. Попыток': 'reaction_time',
        'Сл. Цифровые звуковые сигналы': 'accuracy',
        'Сл. Цифровые звуковые сигналы - Ср. время ус. Попыток': 'reaction_time',
        'Простая РДО - Ср. время с учетом знака': 'reaction_time',
        'Простая РДО - Ст. отклонение с учетом знака': 'standard_deviation',
        'Простая РДО - Ср. время без учета знака': 'reaction_time_module',
        'Простая РДО - Ст. отклонение без учета знака': 'standard_deviation_module',
        'Сложная РДО - Ср. время с учетом знака': 'reaction_time',
        'Сложная РДО - Ст. отклонение с учетом знака': 'standard_deviation',
        'Сложная РДО - Ср. время без учета знака': 'reaction_time_module',
        'Сложная РДО - Ст. отклонение без учета знака': 'standard_deviation_module',
        'Аналоговое слежение - Среднее время': 'reaction_time',
        'Аналоговое преследование - Среднее время реакции': 'average_reaction_time',
        'Аналоговое преследование - Макс. Время пересечения': 'max_intersection_time',
        'Распределение внимания - Среднее время ус. попыток': 'attention_time',
        'Распределение внимания - Ст. отклонение': 'standard_deviation',
        'Распределение внимания - Ошибки': 'mistakes',
        'Устойчивость внимания - Точность': 'accuracy',
        'Устойчивость внимания - Ср. время реакции': 'reaction_time',
        'Устойчивость внимания - Ст. отклонение времени реакции': 'standard_deviation',
        'Звуковая кратковременная память - Точность': 'accuracy',
        'Звуковая кратковременная память - Ср. время реакции': 'reaction_time',
        'Звуковая кратковременная память - Ст. отклонение времени реакции': 'standard_deviation',
        'Мгновенная визуальная память - Точность': 'accuracy',
        'Мгновенная визуальная память - Время реакции': 'reaction_time',
        'Аналитическое мышление - Точность': 'accuracy',
        'Аналитическое мышление - Ср. время реакции': 'reaction_time',
        'Аналитическое мышление - Ст. отклонение времени реакции': 'standard_deviation',
        'Индуктивное мышление - Ср. количество попыток': 'attempts',
        'Индуктивное мышление - Ср. время реакции': 'reaction_time',
        'Индуктивное мышление - Ст. отклонение попыток': 'standard_deviation_attempts',
        'Абстракционное мышление - Точность': 'accuracy',
        'Абстракционное мышление - Ср. время реакции': 'reaction_time',
        'Абстракционное мышление - Ст. отклонение времени реакции': 'standard_deviation'
    };


    const pvkContainer = document.getElementById("pvk-container");

    pvkData.forEach((pvk) => {
        const pvkItem = document.createElement("div");
        pvkItem.className = "pvk-item";
        pvkItem.innerHTML = `
            <h3>${pvk.name}</h3>
            <button onclick="addStat(${pvk.id})">Добавить Статистику</button>
            <div id="stat-list-${pvk.id}"></div>
        `;
        pvkContainer.appendChild(pvkItem);
    });

    loadWeights();

    document.getElementById('save-button').addEventListener('click', () => {
        var weights = [];
        var toAdd = true;
        document.querySelectorAll('input[type="number"]').forEach(input => {
            if (input.value) {
                if (parseFloat(input.value) >= 0) {
                    weights.push({
                        piq_id: parseInt(input.getAttribute('data-pvk-id')),
                        test_id: input.getAttribute('data-test-id'),
                        stat_name: input.getAttribute('data-stat').trim(),
                        weight: parseFloat(input.value)
                    });
                } else {
                    alert("Введите положительные веса!");
                    weights = [];
                    toAdd = false;
                }
            }
        });
        if (toAdd) {
            console.log(weights); // Здесь можно заменить на отправку данных на сервер
            let response = saveWeights(weights);
            // console.log(Object.entries(response));
            if (response === true) {
                alert("Успешно обновлены веса!");
            }
        }
    });

    window.addStat = function (pvkId, statName = '', weight = '') {
        const statList = document.getElementById(`stat-list-${pvkId}`);
        const statContainer = document.createElement('div');
        statContainer.className = 'stat-container';
        const select = document.createElement('select');
        Object.entries(stats).forEach(([key, value]) => {
            const option = document.createElement('option');
            option.value = value;
            option.text = key;
            option.testId = getTestIdByStat(key.split(' - ')[0].trim())
            if (key === statName) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        const input = document.createElement('input');
        input.type = 'number';
        input.min = 0;
        input.max = 100;
        input.step = 0.01;
        input.value = weight;
        input.placeholder = 'Вес';
        input.setAttribute('data-pvk-id', pvkId);

        select.onchange = function (selected = null) {
            const div = document.createElement('div');
            var statKey
            var statText
            var testId
            if (selected instanceof Event) {
                statKey = select.selectedOptions[0].value;
                statText = select.selectedOptions[0].innerText;
                testId = getTestIdByStat(select.selectedOptions[0].innerText.split(' - ')[0].trim());
                console.log(getTestIdByStat(select.selectedOptions[0].innerText.split(' - ')[0].trim()))
            } else {
                statKey = selected.value;
                statText = selected.text;
                testId = selected.testId;
            }
            const deleteButton = document.createElement('button');
            deleteButton.addEventListener('click', () => statContainer.remove());
            deleteButton.innerHTML = "Удалить вес теста";
            deleteButton.className = "delete-button";
            input.setAttribute('data-stat', statKey);
            input.setAttribute('data-stat-text', statText);
            input.setAttribute('data-test-id', testId);

            statContainer.innerHTML = `<span>${statText}</span>`;
            div.appendChild(deleteButton);
            div.appendChild(input);
            statContainer.appendChild(div);
            statList.appendChild(statContainer);
        };

        statContainer.appendChild(select);
        statList.appendChild(statContainer);
        console.log(statName)
        if (statName) {
            select.onchange({value: statName.split(' - ')[1], text: statName.split(' - ')[0], testId: getTestIdByStat(statName.split(' - ')[0])});
        }
    };

    function getTestIdByStat(stat) {
        const statToTestIdMap = {
            'Простые визуальные сигналы': 1,
            'Простые звуковые сигналы': 2,
            'Сложные цветные сигналы': 3,
            'Сл. Цифровые визуальные сигналы': 4,
            'Сл. Цифровые звуковые сигналы': 5,
            'Простая РДО': 6,
            'Сложная РДО': 7,
            'Аналоговое слежение': 8,
            'Аналоговое преследование': 9,
            'Распределение внимания': 10,
            'Устойчивость внимания': 11,
            'Звуковая кратковременная память': 12,
            'Мгновенная визуальная память': 13,
            'Аналитическое мышление': 14,
            'Индуктивное мышление': 15,
            'Абстракционное мышление': 16,
        };
        return statToTestIdMap[stat] || 0;
    }
});

function getStatNameByTestIdAndStat(testId, statName) {
    const testToStatMap = {
        1: "Простые визуальные сигналы",
        2: "Простые звуковые сигналы",
        3: "Сложные цветные сигналы",
        4: "Сл. Цифровые визуальные сигналы",
        5: "Сл. Цифровые звуковые сигналы",
        6: "Простая РДО",
        7: "Сложная РДО",
        8: "Аналоговое слежение",
        9: "Аналоговое преследование",
        10: "Распределение внимания",
        11: "Устойчивость внимания",
        12: "Звуковая кратковременная память",
        13: "Мгновенная визуальная память",
        14: "Аналитическое мышление",
        15: "Индуктивное мышление",
        16: "Абстракционное мышление",
    };
    return `${testToStatMap[testId]} - ${statName}`;
}

function saveWeights(weights) {
    // отправка оценок на серв
    var formData = new FormData();
    formData.append('weights', JSON.stringify(weights));
    console.log(JSON.stringify(weights))
    // этот метод sendData есть на серваке, локально работать не будет
    var result = sendData(formData, '../../backend/requests/set_weights.php');
    result.then(console.log)
    return result.response;
}

function loadWeights() {
    // запрос данных с сервера
    var formData = new FormData();
    var result = getData(formData, "../../backend/requests/get_weights.php");
    result.then((result) => {
        if (result && Array.isArray(result)) {
            result.forEach((item) => {
                const pvkId = item.piq_id;
                const statName = getStatNameByTestIdAndStat(item.test_id, item.stat_name);
                const weight = item.weight;
                addStat(pvkId, statName, weight);
            });
        }
    });
}