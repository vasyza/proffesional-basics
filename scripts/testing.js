import {getData} from './data_manager.js';

const ctx = document.getElementById('testing_chart').getContext('2d');

var data = getData(null, '../backend/requests/get_testing_results.php');

data.then((data) => {
    var results = {
        1: data.response[0],
        2: data.response[1],
        3: data.response[2]
    }
    console.log(data, results);

    var myChart = new Chart(ctx, {
        type: 'line',
        data: {
            datasets: [{
                label: "Результаты тестирования",
                data: results,
                borderWidth: 1
            }]
        }
    });
});
