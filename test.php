<!DOCTYPE html>
<html>
 <head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="css/expert-panel.css">
  <title>ITMO.TEST</title>
 </head>
 <body>
    <!-- Обратботка ренетерна -->
    <header class="header">
        <div class="header logo">
            <a href="mainpage.html">
                <img src="./../../img/ITMOMENT_logo.png" alt="Кнопка «input»">
            </a>
        </div>
        <div class="header name">
            <h1>Тест: "Профессии и качества"</h1>
        </div>
        <a href="cabinet.html" class="btnn"><button class="btnn">Вернуться в профиль</button></a>
    </header>

    <form id="test1" onsubmit="return CheckForm(this)" action="expert.php">

        <p>Выберите от 5 до 10 самых важных качеств для профессии</p>
        <p>Проставьте им баллы по важности (от 1 до 9)</p>

        <div class="prof">
            <select class="prof" name="prof">
                <option value="" selected disabled>Выберите профессию</option>
                    <?php
                        $link = mysqli_connect("VH297.spaceweb.ru", "hogdaw1gma", "mUhNf!JELM349ii", "hogdaw1gma");
                        $query = "SELECT name_of_profession FROM profession_describe";
                        $result = mysqli_query($link, $query) or die(mysqli_error($link));
                        while($row = mysqli_fetch_assoc($result)) {
                            echo '<option value="' . $row['name_of_profession'] . '">' . $row['name_of_profession'] . '</option>';
                        }
                    ?>
            </select>
        </div>

        <!-- Скрипт-счётчик чекбоксов, нужно добавить визуальную часть. @Awwac PLEASE!!!!!!!! -->

        <script>
            function checkboxes(){
                var inputElems = document.getElementsByTagName("input"),
                count = 0;
                for (var i=0; i<inputElems.length; i++) {
                    if (inputElems[i].type === "checkbox" && inputElems[i].checked === true){
                        count++;
                        alert(count);
                    }
                }
            }
        </script>
          <br><br>
          <div class = "checkbox">
					<span>1) Готовность к защите Родины с оружием в руках </span><br></br>
          <input type="checkbox" id="que1" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer1')">
          <label for="bebra"> Нужно </label><br>
          <div id="inputContainer1" style="display: none;">
              <input type="text" id="inputField1" placeholder="Введите ваш ответ">
					</div>
          </div>


        <div class = "checkbox">
					<span>2) Военно-профессиональная направленность</span><br></br>
        <input type="checkbox" id="que2" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer2')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer2" style="display: none;">
            <input type="text" id="inputField2" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>3) Прямые внутренние мотивы военно-профессиональной деятельности</span><br></br>
        <input type="checkbox" id="que3" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer3')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer3" style="display: none;">
            <input type="text" id="inputField3" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>4) Стремление к профессиональному совершенству</span><br></br>
        <input type="checkbox" id="que4" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer4')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer4" style="display: none;">
            <input type="text" id="inputField4" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>5) Адекватная самооценка</span><br></br>
        <input type="checkbox" id="que5" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer5')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer5" style="display: none;">
            <input type="text" id="inputField5" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>6) Самостоятельность</span><br></br>
        <input type="checkbox" id="que6" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer6')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer6" style="display: none;">
            <input type="text" id="inputField6" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>7) Пунктуальность, педантичность</span><br></br>
        <input type="checkbox" id="que7" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer7')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer7" style="display: none;">
            <input type="text" id="inputField7" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>8) Дисциплинированность</span><br></br>
        <input type="checkbox" id="que8" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer8')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer8" style="display: none;">
            <input type="text" id="inputField8" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>9) Аккуратность в работе</span><br></br>
        <input type="checkbox" id="que9" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer9')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer9" style="display: none;">
            <input type="text" id="inputField9" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>10) Организованность, самодисциплина</span><br></br>
        <input type="checkbox" id="que10" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer10')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer10" style="display: none;">
            <input type="text" id="inputField10" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>11) Старательность, исполнительность</span><br></br>
        <input type="checkbox" id="que11" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer11')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer11" style="display: none;">
            <input type="text" id="inputField11" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>12) Ответственность</span><br></br>
        <input type="checkbox" id="que12" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer12')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer12" style="display: none;">
            <input type="text" id="inputField12" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>13) Трудолюбие</span><br></br>
        <input type="checkbox" id="que13" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer13')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer13" style="display: none;">
            <input type="text" id="inputField13" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>14) Инициативность</span><br></br>
        <input type="checkbox" id="que14" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer14')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer14" style="display: none;">
            <input type="text" id="inputField14" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>15) Самокритичность</span><br></br>
        <input type="checkbox" id="que15" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer15')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer15" style="display: none;">
            <input type="text" id="inputField15" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>16) Оптимизм, доминирование положительных эмоций </span><br></br>
        <input type="checkbox" id="que16" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer16')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer16" style="display: none;">
            <input type="text" id="inputField16" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>17) Самообладание, эмоциональная уравновешенность, выдержка</span><br></br>
        <input type="checkbox" id="que17" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer17')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer17" style="display: none;">
            <input type="text" id="inputField17" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>18) Самоконтроль, способность к самонаблюдению</span><br></br>
        <input type="checkbox" id="que18" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer18')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer18" style="display: none;">
            <input type="text" id="inputField18" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>19) Предусмотрительность</span><br></br>
        <input type="checkbox" id="que19" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer19')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer19" style="display: none;">
            <input type="text" id="inputField19" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>20) Фрустрационная толерантность</span><br></br>
        <input type="checkbox" id="que20" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer20')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer20" style="display: none;">
            <input type="text" id="inputField20" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>21) Самомобилизующийся тип реакции на препятствия, возникающие на пути к достижению цели</span><br></br>
        <input type="checkbox" id="que21" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer21')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer21" style="display: none;">
            <input type="text" id="inputField21" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>22) Интернальность</span><br></br>
        <input type="checkbox" id="que22" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer22')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer22" style="display: none;">
            <input type="text" id="inputField22" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>23) Экстернальность</span><br></br>
        <input type="checkbox" id="que23" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer23')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer23" style="display: none;">
            <input type="text" id="inputField23" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>24) Интрапунитивность</span><br></br>
        <input type="checkbox" id="que24" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer24')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer24" style="display: none;">
            <input type="text" id="inputField24" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>25) Экстрапунитивность</span><br></br>
        <input type="checkbox" id="que25" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer25')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer25" style="display: none;">
            <input type="text" id="inputField25" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>26) Способность планировать свою деятельность во времени</span><br></br>
        <input type="checkbox" id="que26" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer26')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer26" style="display: none;">
            <input type="text" id="inputField26" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>27) Способность организовывать свою деятельность в условиях большого потока информации и разнообразия поставленных задач</span><br></br>
        <input type="checkbox" id="que27" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer27')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer27" style="display: none;">
            <input type="text" id="inputField27" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>28) Способность брать на себя ответственность за принимаемые решения и действия</span><br></br>
        <input type="checkbox" id="que28" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer28')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer28" style="display: none;">
            <input type="text" id="inputField28" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>29) Способность принимать решение в нестандартных ситуациях</span><br></br>
        <input type="checkbox" id="que29" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer29')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer29" style="display: none;">
            <input type="text" id="inputField29" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>30) Способность рационально действовать в экстремальных ситуациях</span><br></br>
        <input type="checkbox" id="que30" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer30')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer30" style="display: none;">
            <input type="text" id="inputField30" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>31) Способность эффективно действовать (также быстро принимать решения) в условиях дефицита времени</span><br></br>
        <input type="checkbox" id="que31" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer31')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer31" style="display: none;">
            <input type="text" id="inputField31" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>32) Способность переносить неприятные ощущения без потрясений</span><br></br>
        <input type="checkbox" id="que32" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer32')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer32" style="display: none;">
            <input type="text" id="inputField32" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>33) Способность аргументировано отстаивать свое мнение</span><br></br>
        <input type="checkbox" id="que33" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer33')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer33" style="display: none;">
            <input type="text" id="inputField33" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>34) Способность к переключениям с одной деятельности на другую</span><br></br>
        <input type="checkbox" id="que34" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer34')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer34" style="display: none;">
            <input type="text" id="inputField34" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>35) Способность преодолевать страх</span><br></br>
        <input type="checkbox" id="que35" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer35')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer35" style="display: none;">
            <input type="text" id="inputField35" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>36) Зрительная оценка размеров предметов</span><br></br>
        <input type="checkbox" id="que36" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer36')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer36" style="display: none;">
            <input type="text" id="inputField36" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>37) Зрительное восприятие расстояний между предметами</span><br></br>
        <input type="checkbox" id="que37" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer37')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer37" style="display: none;">
            <input type="text" id="inputField37" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>38) Глазомер: линейный, угловой, объемный</span><br></br>
        <input type="checkbox" id="que38" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer38')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer38" style="display: none;">
            <input type="text" id="inputField38" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>39) Глазомер динамический</span><br></br>
        <input type="checkbox" id="que39" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer39')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer39" style="display: none;">
            <input type="text" id="inputField39" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>40) Способность к различению фигуры на малоконтрастном фоне</span><br></br>
        <input type="checkbox" id="que40" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer40')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer40" style="display: none;">
            <input type="text" id="inputField40" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>41) Способность различать и опознавать замаскированные объекты</span><br></br>
        <input type="checkbox" id="que41" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer41')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer41" style="display: none;">
            <input type="text" id="inputField41" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>42) Способность к восприятию пространственного соотношения предметов</span><br></br>
        <input type="checkbox" id="que42" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer42')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer42" style="display: none;">
            <input type="text" id="inputField42" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>43) Точность и оценка направления на источник звука</span><br></br>
        <input type="checkbox" id="que43" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer43')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer43" style="display: none;">
            <input type="text" id="inputField43" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>44) Способность узнавать и различать ритмы</span><br></br>
        <input type="checkbox" id="que44" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer44')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer44" style="display: none;">
            <input type="text" id="inputField44" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>45) Речевой слух</span><br></br>
        <input type="checkbox" id="que45" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer45')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer45" style="display: none;">
            <input type="text" id="inputField45" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>46) Различение отрезков времени</span><br></br>
        <input type="checkbox" id="que46" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer46')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer46" style="display: none;">
            <input type="text" id="inputField46" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>47) Способность к распознаванию небольших отклонений параметров технологических процессов от заданных значений по визуальным признакам</span><br></br>
        <input type="checkbox" id="que47" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer47')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer47" style="display: none;">
            <input type="text" id="inputField47" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>48) Способность к распознаванию небольших отклонений параметров технологических процессов от заданных значений по акустическим признакам</span><br></br>
        <input type="checkbox" id="que48" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer48')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer48" style="display: none;">
            <input type="text" id="inputField48" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>49) Способность к распознаванию небольших отклонений параметров технологических процессов от заданных значений по кинестетическим признакам</span><br></br>
        <input type="checkbox" id="que49" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer49')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer49" style="display: none;">
            <input type="text" id="inputField49" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>50) Способность к зрительным представлениям</span><br></br>
        <input type="checkbox" id="que50" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer50')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer50" style="display: none;">
            <input type="text" id="inputField50" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>51) Способность к пространственному воображению</span><br></br>
        <input type="checkbox" id="que51" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer51')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer51" style="display: none;">
            <input type="text" id="inputField51" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>52) Способность к образному представлению предметов, процессов и явлений</span><br></br>
        <input type="checkbox" id="que52" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer52')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer52" style="display: none;">
            <input type="text" id="inputField52" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>53) Способность наглядно представлять себе новое явление, ранее, не встречающееся в опыте, или старое, но в новых условиях</span><br></br>
        <input type="checkbox" id="que53" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer53')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer53" style="display: none;">
            <input type="text" id="inputField53" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>54) Способность к переводу образа в словесное описание</span><br></br>
        <input type="checkbox" id="que54" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer54')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer54" style="display: none;">
            <input type="text" id="inputField54" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>55) Способность к воссозданию образа по словесному описанию</span><br></br>
        <input type="checkbox" id="que55" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer55')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer55" style="display: none;">
            <input type="text" id="inputField55" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>56) Аналитичность</span><br></br>
        <input type="checkbox" id="que56" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer56')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer56" style="display: none;">
            <input type="text" id="inputField56" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>57) Синтетичность</span><br></br>
        <input type="checkbox" id="que57" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer57')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer57" style="display: none;">
            <input type="text" id="inputField57" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>58) Транссонантность</span><br></br>
        <input type="checkbox" id="que58" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer58')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer58" style="display: none;">
            <input type="text" id="inputField58" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>59) Логичность</span><br></br>
        <input type="checkbox" id="que59" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer59')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer59" style="display: none;">
            <input type="text" id="inputField59" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>60) Креативность</span><br></br>
        <input type="checkbox" id="que60" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer60')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer60" style="display: none;">
            <input type="text" id="inputField60" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>61) Оперативность</span><br></br>
        <input type="checkbox" id="que61" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer61')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer61" style="display: none;">
            <input type="text" id="inputField61" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>62) Предметность</span><br></br>
        <input type="checkbox" id="que62" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer62')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer62" style="display: none;">
            <input type="text" id="inputField62" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>63) Образность</span><br></br>
        <input type="checkbox" id="que63" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer63')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer63" style="display: none;">
            <input type="text" id="inputField63" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>64) Абстрактность</span><br></br>
        <input type="checkbox" id="que64" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer64')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer64" style="display: none;">
            <input type="text" id="inputField64" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>65) Вербальность</span><br></br>
        <input type="checkbox" id="que65" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer65')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer65" style="display: none;">
            <input type="text" id="inputField65" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>66) Калькулятивность</span><br></br>
        <input type="checkbox" id="que66" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer66')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer66" style="display: none;">
            <input type="text" id="inputField66" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>67) Энергичность, витальность</span><br></br>
        <input type="checkbox" id="que67" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer67')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer67" style="display: none;">
            <input type="text" id="inputField67" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>68) Умственная работоспособность</span><br></br>
        <input type="checkbox" id="que68" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer68')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer68" style="display: none;">
            <input type="text" id="inputField68" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>69) Физическая работоспособность</span><br></br>
        <input type="checkbox" id="que69" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer69')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer69" style="display: none;">
            <input type="text" id="inputField69" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>70) Нервно-эмоциональная устойчивость, выносливость по отношению к эмоциональным нагрузкам</span><br></br>
        <input type="checkbox" id="que70" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer70')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer70" style="display: none;">
            <input type="text" id="inputField70" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>71) Острота зрения</span><br></br>
        <input type="checkbox" id="que71" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer71')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer71" style="display: none;">
            <input type="text" id="inputField71" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>72) Адаптация зрения к темноте, свету</span><br></br>
        <input type="checkbox" id="que72" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer72')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer72" style="display: none;">
            <input type="text" id="inputField72" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>73) Контрастная чувствительность монохроматического зрения</span><br></br>
        <input type="checkbox" id="que73" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer73')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer73" style="display: none;">
            <input type="text" id="inputField73" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>74) Цветовая дифференциальная чувствительность</span><br></br>
        <input type="checkbox" id="que74" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer74')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer74" style="display: none;">
            <input type="text" id="inputField74" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>75) Устойчивость зрительной чувствительности во времени</span><br></br>
        <input type="checkbox" id="que75" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer75')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer75" style="display: none;">
            <input type="text" id="inputField75" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>76) Острота слуха</span><br></br>
        <input type="checkbox" id="que76" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer76')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer76" style="display: none;">
            <input type="text" id="inputField76" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>77) Контрастная чувствительность слуха</span><br></br>
        <input type="checkbox" id="que77" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer77')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer77" style="display: none;">
            <input type="text" id="inputField77" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>78) Слуховая дифференциальная чувствительность</span><br></br>
        <input type="checkbox" id="que78" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer78')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer78" style="display: none;">
            <input type="text" id="inputField78" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>79) Переносимость длительно действующего звукового раздражителя</span><br></br>
        <input type="checkbox" id="que79" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer79')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer79" style="display: none;">
            <input type="text" id="inputField79" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>80) Чувствительность (осязание) пальцев </span><br></br>
        <input type="checkbox" id="que80" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer80')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer80" style="display: none;">
            <input type="text" id="inputField80" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>81) Вибрационная чувствительность</span><br></br>
        <input type="checkbox" id="que81" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer81')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer81" style="display: none;">
            <input type="text" id="inputField81" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>82) Мышечно-суставная чувствительность усилий или сопротивления</span><br></br>
        <input type="checkbox" id="que82" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer82')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer82" style="display: none;">
            <input type="text" id="inputField82" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>83) Ощущение равновесия</span><br></br>
        <input type="checkbox" id="que83" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer83')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer83" style="display: none;">
            <input type="text" id="inputField83" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>84) Ощущение ускорения</span><br></br>
        <input type="checkbox" id="que84" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer84')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer84" style="display: none;">
            <input type="text" id="inputField84" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>85) Обонятельная чувствительность</span><br></br>
        <input type="checkbox" id="que85" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer85')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer85" style="display: none;">
            <input type="text" id="inputField85" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>86) Способность узнавать и различать вкусовые ощущения</span><br></br>
        <input type="checkbox" id="que86" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer86')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer86" style="display: none;">
            <input type="text" id="inputField86" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>87) Объем внимания</span><br></br>
        <input type="checkbox" id="que87" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer87')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer87" style="display: none;">
            <input type="text" id="inputField87" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>88) Концентрированность внимания</span><br></br>
        <input type="checkbox" id="que88" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer88')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer88" style="display: none;">
            <input type="text" id="inputField88" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>89) Устойчивость внимания во времени</span><br></br>
        <input type="checkbox" id="que89" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer89')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer89" style="display: none;">
            <input type="text" id="inputField89" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>90) Переключаемость внимания</span><br></br>
        <input type="checkbox" id="que90" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer90')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer90" style="display: none;">
            <input type="text" id="inputField90" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>91) Способность к распределению внимания между несколькими объектами или видами деятельности</span><br></br>
        <input type="checkbox" id="que91" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer91')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer91" style="display: none;">
            <input type="text" id="inputField91" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>92) Помехоустойчивость внимания</span><br></br>
        <input type="checkbox" id="que92" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer92')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer92" style="display: none;">
            <input type="text" id="inputField92" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>93) Способность подмечать изменения в окружающей обстановке, не сосредотачивая сознательно на них внимание</span><br></br>
        <input type="checkbox" id="que93" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer93')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer93" style="display: none;">
            <input type="text" id="inputField93" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>94) Умение подмечать незначительные изменения в исследуемом объекте, в показаниях приборов </span><br></br>
        <input type="checkbox" id="que94" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer94')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer94" style="display: none;">
            <input type="text" id="inputField94" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>95) Способность реагировать на неожиданный зрительный сигнал посредством определённых движений</span><br></br>
        <input type="checkbox" id="que95" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer95')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer95" style="display: none;">
            <input type="text" id="inputField95" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>96) Способность реагировать на неожиданный слуховой сигнал посредством определённых движений</span><br></br>
        <input type="checkbox" id="que96" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer96')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer96" style="display: none;">
            <input type="text" id="inputField96" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>97) Согласованность движений с процессами восприятия (сложноорганизованная деятельность)</span><br></br>
        <input type="checkbox" id="que97" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer97')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer97" style="display: none;">
            <input type="text" id="inputField97" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>98) Способность к сенсомоторному слежению за движущимся объектом</span><br></br>
        <input type="checkbox" id="que98" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer98')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer98" style="display: none;">
            <input type="text" id="inputField98" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>99) Способность к выполнению мелких точных движений</span><br></br>
        <input type="checkbox" id="que99" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer99')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer99" style="display: none;">
            <input type="text" id="inputField99" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>100) Способность к выполнению сложных двигательных действий (актов)</span><br></br>
        <input type="checkbox" id="que100" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer100')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer100" style="display: none;">
            <input type="text" id="inputField100" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>101) Способность к выполнению плавных соразмерных движений</span><br></br>
        <input type="checkbox" id="que101" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer101')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer101" style="display: none;">
            <input type="text" id="inputField101" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>102) Координация движений ведущей руки</span><br></br>
        <input type="checkbox" id="que102" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer102')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer102" style="display: none;">
            <input type="text" id="inputField102" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>103) Координация движений обеих рук</span><br></br>
        <input type="checkbox" id="que103" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer103')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer103" style="display: none;">
            <input type="text" id="inputField103" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>104) Координация движений рук и ног</span><br></br>
        <input type="checkbox" id="que104" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer104')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer104" style="display: none;">
            <input type="text" id="inputField104" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>105) Координация работы кистей рук и пальцев</span><br></br>
        <input type="checkbox" id="que105" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer105')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer105" style="display: none;">
            <input type="text" id="inputField105" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>106) Твердость руки, устойчивость кистей рук (низкий тремор)</span><br></br>
        <input type="checkbox" id="que106" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer106')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer106" style="display: none;">
            <input type="text" id="inputField106" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>107) Умение быстро записывать</span><br></br>
        <input type="checkbox" id="que107" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer107')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer107" style="display: none;">
            <input type="text" id="inputField107" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>108) Красивый почерк</span><br></br>
        <input type="checkbox" id="que108" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer108')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer108" style="display: none;">
            <input type="text" id="inputField108" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>109) Физическая сила</span><br></br>
        <input type="checkbox" id="que109" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer109')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer109" style="display: none;">
            <input type="text" id="inputField109" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>110) Способность к быстрой выработке сенсомоторных навыков</span><br></br>
        <input type="checkbox" id="que110" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer110')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer110" style="display: none;">
            <input type="text" id="inputField110" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>111) Способность к быстрой перестройке сенсомоторных навыков</span><br></br>
        <input type="checkbox" id="que111" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer111')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer111" style="display: none;">
            <input type="text" id="inputField111" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>112) Пластичность и выразительность движений</span><br></br>
        <input type="checkbox" id="que112" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer112')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer112" style="display: none;">
            <input type="text" id="inputField112" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>113) Отсутствие дефектов речи, хорошая дикция</span><br></br>
        <input type="checkbox" id="que113" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer113')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer113" style="display: none;">
            <input type="text" id="inputField113" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>114) Способность речевого аппарата к интенсивной и длительной работе</span><br></br>
        <input type="checkbox" id="que114" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer114')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer114" style="display: none;">
            <input type="text" id="inputField114" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>115) Способность к изменению тембра</span><br></br>
        <input type="checkbox" id="que115" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer115')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer115" style="display: none;">
            <input type="text" id="inputField115" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>116) Способность к изменению силы звучания</span><br></br>
        <input type="checkbox" id="que116" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer116')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer116" style="display: none;">
            <input type="text" id="inputField116" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>117) Переносимость динамических физических нагрузок</span><br></br>
        <input type="checkbox" id="que117" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer117')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer117" style="display: none;">
            <input type="text" id="inputField117" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>118) Переносимость статических физических нагрузок</span><br></br>
        <input type="checkbox" id="que118" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer118')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer118" style="display: none;">
            <input type="text" id="inputField118" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>119) Быстрый переход из состояния покоя к интенсивной работе</span><br></br>
        <input type="checkbox" id="que119" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer119')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer119" style="display: none;">
            <input type="text" id="inputField119" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>120) Сохранение работоспособности при недостатке сна</span><br></br>
        <input type="checkbox" id="que120" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer120')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer120" style="display: none;">
            <input type="text" id="inputField120" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>121) Сохранение работоспособности при развивающемся утомлении</span><br></br>
        <input type="checkbox" id="que121" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer121')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer121" style="display: none;">
            <input type="text" id="inputField121" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>122) Сохранение бдительности в условиях однообразной деятельности (монотонии)</span><br></br>
        <input type="checkbox" id="que122" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer122')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer122" style="display: none;">
            <input type="text" id="inputField122" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>123) Сохранение бдительности в режиме ожидания</span><br></br>
        <input type="checkbox" id="que123" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer123')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer123" style="display: none;">
            <input type="text" id="inputField123" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>124) Сохранение работоспособности в некомфортных температурных условиях</span><br></br>
        <input type="checkbox" id="que124" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer124')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer124" style="display: none;">
            <input type="text" id="inputField124" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>125) Сохранение работоспособности в условиях знакопеременных перегрузок</span><br></br>
        <input type="checkbox" id="que125" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer125')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer125" style="display: none;">
            <input type="text" id="inputField125" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>126) Сохранение работоспособности в условиях воздействия вибрации</span><br></br>
        <input type="checkbox" id="que126" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer126')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer126" style="display: none;">
            <input type="text" id="inputField126" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>127) Сохранение работоспособности в условиях воздействия разнонаправленных перегрузок</span><br></br>
        <input type="checkbox" id="que127" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer127')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer127" style="display: none;">
            <input type="text" id="inputField127" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>128) Сохранение работоспособности в условиях гипо(гипер) барометрических колебаний</span><br></br>
        <input type="checkbox" id="que128" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer128')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer128" style="display: none;">
            <input type="text" id="inputField128" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>129) Сохранение работоспособности в условиях пониженного парциального давления кислорода</span><br></br>
        <input type="checkbox" id="que129" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer129')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer129" style="display: none;">
            <input type="text" id="inputField129" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>130) Сохранение работоспособности в условиях пониженного парциального давления углекислого газа</span><br></br>
        <input type="checkbox" id="que130" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer130')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer130" style="display: none;">
            <input type="text" id="inputField130" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>131) Сохранение работоспособности в условиях ограничения возможностей удовлетворения базовых жизненных потребностей</span><br></br>
        <input type="checkbox" id="que131" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer131')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer131" style="display: none;">
            <input type="text" id="inputField131" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>132) Сохранение работоспособности в разных природно-климатических условиях</span><br></br>
        <input type="checkbox" id="que132" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer132')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer132" style="display: none;">
            <input type="text" id="inputField132" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>133) Способность переадаптироваться к новым средовым условиям</span><br></br>
        <input type="checkbox" id="que133" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer133')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer133" style="display: none;">
            <input type="text" id="inputField133" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>134) Антропометрические характеристики (в соответствии с требованиями профессии)</span><br></br>
        <input type="checkbox" id="que134" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer134')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer134" style="display: none;">
            <input type="text" id="inputField134" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>135) Особенности телосложения (в соответствии с требованиями профессии) </span><br></br>
        <input type="checkbox" id="que135" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer135')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer135" style="display: none;">
            <input type="text" id="inputField135" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>136) Хорошее общее физическое развитие – выносливость, координированность, сила, быстрота </span><br></br>
        <input type="checkbox" id="que136" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer136')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer136" style="display: none;">
            <input type="text" id="inputField136" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>137) Физическая подготовленность к воздействию неблагоприятных факторов профессиональной деятельности</span><br></br>
        <input type="checkbox" id="que137" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer137')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer137" style="display: none;">
            <input type="text" id="inputField137" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>138) Зрительная долговременная память на лица</span><br></br>
        <input type="checkbox" id="que138" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer138')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer138" style="display: none;">
            <input type="text" id="inputField138" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>139) Зрительная долговременная память на образы предметного мира</span><br></br>
        <input type="checkbox" id="que139" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer139')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer139" style="display: none;">
            <input type="text" id="inputField139" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>140) Зрительная долговременная память на условные обозначения (знаки, символы, планы, схемы, графики)</span><br></br>
        <input type="checkbox" id="que140" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer140')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer140" style="display: none;">
            <input type="text" id="inputField140" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>141) Зрительная долговременная память на цифры, даты</span><br></br>
        <input type="checkbox" id="que141" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer141')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer141" style="display: none;">
            <input type="text" id="inputField141" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>142) Зрительная долговременная память на слова и фразы</span><br></br>
        <input type="checkbox" id="que142" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer142')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer142" style="display: none;">
            <input type="text" id="inputField142" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>143) Зрительная долговременная память на семантику текста</span><br></br>
        <input type="checkbox" id="que143" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer143')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer143" style="display: none;">
            <input type="text" id="inputField143" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>144) Зрительная оперативная память на лица</span><br></br>
        <input type="checkbox" id="que144" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer144')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer144" style="display: none;">
            <input type="text" id="inputField144" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>145) Зрительная оперативная память на образы предметного мира</span><br></br>
        <input type="checkbox" id="que145" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer145')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer145" style="display: none;">
            <input type="text" id="inputField145" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>146) Зрительная оперативная память на условные обозначения (знаки, символы, планы, схемы, графики)</span><br></br>
        <input type="checkbox" id="que146" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer146')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer146" style="display: none;">
            <input type="text" id="inputField146" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>147) Зрительная оперативная память на цифры,  даты</span><br></br>
        <input type="checkbox" id="que147" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer147')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer147" style="display: none;">
            <input type="text" id="inputField147" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>148) Зрительная оперативная память на слова и фразы</span><br></br>
        <input type="checkbox" id="que148" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer148')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer148" style="display: none;">
            <input type="text" id="inputField148" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>149) Зрительная оперативная память на семантику текста</span><br></br>
        <input type="checkbox" id="que149" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer149')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer149" style="display: none;">
            <input type="text" id="inputField149" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>150) Слуховая долговременная память на голоса</span><br></br>
        <input type="checkbox" id="que150" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer150')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer150" style="display: none;">
            <input type="text" id="inputField150" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>151) Слуховая долговременная память на цифры</span><br></br>
        <input type="checkbox" id="que151" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer151')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer151" style="display: none;">
            <input type="text" id="inputField151" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>152) Слуховая долговременная память на условные сигналы</span><br></br>
        <input type="checkbox" id="que152" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer152')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer152" style="display: none;">
            <input type="text" id="inputField152" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>153) Слуховая долговременная память на мелодии</span><br></br>
        <input type="checkbox" id="que153" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer153')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer153" style="display: none;">
            <input type="text" id="inputField153" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>154) Слуховая долговременная память на семантику сообщений</span><br></br>
        <input type="checkbox" id="que154" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer154')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer154" style="display: none;">
            <input type="text" id="inputField154" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>155) Слуховая оперативная память на цифры</span><br></br>
        <input type="checkbox" id="que155" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer155')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer155" style="display: none;">
            <input type="text" id="inputField155" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>156) Слуховая оперативная память на семантику сообщений</span><br></br>
        <input type="checkbox" id="que156" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer156')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer156" style="display: none;">
            <input type="text" id="inputField156" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>157) Кинестетическая (моторная) память на простые движения</span><br></br>
        <input type="checkbox" id="que157" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer157')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer157" style="display: none;">
            <input type="text" id="inputField157" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>158) Кинестетическая (моторная) память на сложные движения</span><br></br>
        <input type="checkbox" id="que158" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer158')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer158" style="display: none;">
            <input type="text" id="inputField158" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>159) Кинестетическая (моторная) память на положение и перемещение тела в пространстве</span><br></br>
        <input type="checkbox" id="que159" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer159')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer159" style="display: none;">
            <input type="text" id="inputField159" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>160) Тактильная память</span><br></br>
        <input type="checkbox" id="que160" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer160')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer160" style="display: none;">
            <input type="text" id="inputField160" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>161) Обонятельная память </span><br></br>
        <input type="checkbox" id="que161" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer161')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer161" style="display: none;">
            <input type="text" id="inputField161" placeholder="Введите ваш ответ">
					</div>
        </div>

        <div class = "checkbox">
					<span>162) Вкусовая память</span><br></br>
        <input type="checkbox" id="que162" name="checkbox" value="no" onclick="toggleInput1(this, 'inputContainer162')">
        <label for="bebra"> Нужно </label><br>
        <div id="inputContainer162" style="display: none;">
            <input type="text" id="inputField162" placeholder="Введите ваш ответ">
					</div>
        </div>



        <script>

            function toggleInput1(twt, string) {
                var chk = string.toString();
                twt.value=twt.checked ? 'yes' : 'no'
                var inputContainer = document.getElementById(chk);
                if (inputContainer.style.display === "none") {
                    inputContainer.style.display = "block";
                } else {
                    inputContainer.style.display = "none";
                }
            }

        </script>


        <div id="error" style="color:crimson"></div>
        <button class="button" name="submit" type="submit" value="Готово">Готово</button>


    </form>

    <script src="professionCheck.js"></script>

</body>
</html>
