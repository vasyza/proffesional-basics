import { getData } from './data_manager.js';

// окно для динамики
const chartWindow = document.getElementById("chart");
// кнопка закрытия окна
const closeWindow = document.getElementById("close_chart_window");
// кнопки открытия окон
const openWindows = document.getElementsByName("show_my_dynamic");
// канва графика
const ctx = document.getElementById('my_chart').getContext('2d');
// график
var myChart = new Chart(ctx);

// закрытие при нажатии крестика
closeWindow.addEventListener('click', () => {
    chartWindow.style.display = 'none';
    myChart.destroy();
});

// закрытие при клике снаружи окна
chartWindow.addEventListener('click', (e) => {
    if (e.target === chartWindow) {
        chartWindow.style.display = 'none';
        myChart.destroy();
    }
});

// открыть окно кнопками
openWindows.forEach((button) => {
    var testId = button.getAttribute("test_id");
    button.addEventListener('click', async () => {
        chartWindow.style.display = 'block';

        let formData = new FormData();
        formData.append("test_id", testId);

        var jsonResults = getData(formData, '../backend/requests/get_user_results.php');

        jsonResults.then(showChart);

    });
});

function showChart(result) {
    var userResults;
    if (result.response != null) {
        // console.log(result);
        document.getElementById("window_message").innerHTML = "Динамика результатов";
        userResults = new Map(Object.entries(result.response));

        // мапа по типу стата: среднее значение статы
        var statsMap = {};
        // даты
        var testingDates = {};

        userResults.keys().forEach((key) => {
            let result = userResults.get(key);

            Object.entries(result.statistics).forEach(([stat, value]) => {
                var statMap = Object(statsMap[stat]);
                statMap[result.testing_date] = value;
                statsMap[stat] = statMap;
            });

        });


        console.log(statsMap);

        myChart.destroy();
        myChart = new Chart(ctx, {
            type: 'line'
        });
        console.log(statsMap);
        Object.entries(statsMap).forEach(([stat, values]) => {
            myChart.data.datasets.push({
                label: stat,
                data: values,
                borderWidth: 1
            });
        });
        myChart.update();

    } else {
        // результатов нет
        document.getElementById("window_message").innerHTML = "Вы ещё не проходили этот тест";
    }
}