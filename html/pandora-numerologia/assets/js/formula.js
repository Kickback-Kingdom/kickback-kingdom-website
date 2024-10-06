
let alphaTable = {
    'A': 1,
    'B': 2,
    'C': 3,
    'D': 4,
    'E': 5,
    'F': 6,
    'G': 7,
    'H': 8,
    'I': 9,
    'J': 1,
    'K': 2,
    'L': 3,
    'M': 4,
    'N': 5,
    'O': 6,
    'P': 7,
    'Q': 8,
    'R': 9,
    'S': 1,
    'T': 2,
    'U': 3,
    'V': 4,
    'W': 5,
    'X': 6,
    'Y': 7,
    'Z': 8
};

function isVowel(character) {
    let vowels = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U'];
    return vowels.includes(character);
}

function removeAccents(str) {
    return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
}

function GetFullName()
{
    return removeAccents(document.getElementById("fname").value.trim());
}

function GetProfessionalName()
{
    return removeAccents(document.getElementById("profName").value.trim());
}

function GetDOB() {
    var dateValue = document.getElementById("dob").value;
    var parts = dateValue.split('-');
    return parts[2] + "/" + parts[1] + "/" + parts[0]; // Format it as DD/MM/YYYY
}
function GetDOBLocal() {
    var dateValue = document.getElementById("dob").value;
    var parts = dateValue.split('-'); // parts[0] = year, parts[1] = month, parts[2] = day
    var date = new Date(parts[0], parts[1] - 1, parts[2]); // Construct a Date object, month is 0-indexed
    return date.toLocaleDateString(); // Format it according to the user's locale
}


function GetDOBDay() {
    var dateValue = document.getElementById("dob").value;
    return parseInt(dateValue.split('-')[2]);
}

function GetDOBMonth() {
    var dateValue = document.getElementById("dob").value;
    return parseInt(dateValue.split('-')[1]);
}

function GetDOBYear() {
    var dateValue = document.getElementById("dob").value;
    return parseInt(dateValue.split('-')[0]);
}

function GetHouseNumber()
{
    return parseInt(document.getElementById("houseNum").value.trim());
}

function SetSoulNumber(num)
{
    document.getElementById("soulNumResult").innerText = num;
}

const soulSumMode = {"Vowels":0, "Consonants":1, "Everything": 2};
function GetSoulNumbers(str, mode) {
    var nums = [];
    for(let i = 0; i < str.length; i++) {
        var char = str[i].toUpperCase();
        var val = alphaTable[char];

        switch (mode) {
            case soulSumMode.Vowels:
            if (isVowel(char))
            {
                nums.push(val);
            }
                break;
            case soulSumMode.Consonants:
            if (!isVowel(char))
            {
                nums.push(val);
            }
                break;
            case soulSumMode.Everything:
            
            nums.push(val);
                break;
        
        }
    }

    return nums;
}

function GetResults()
{
    var fullName = GetFullName();
    var names = fullName.split(" ");
    var resultSoulNumbers = [];
    var result = {};
    result["fullName"] = {};
    result["fullName"]["vowels"] = {};
    result["fullName"]["consonants"] = {};
    result["fullName"]["names"] = {};
    result["fullName"]["all"] = {};
    result["calculations"] = {"destiny": {}, "lifeLesson": {}, "powerfulNumber": {}, "lifeChallenges": {}, "personalDates": {}, "phasesOfLife": {}, "professionalName": {}};
    result["dob"] = {"date": GetDOB()};
    
    result["fullName"]["name"] = fullName;

    console.log("Vowels");
    for (var i = 0; i < names.length; i++)
    {
        var name = names[i];
        var soulNumbers = GetSoulNumbers(name, soulSumMode.Vowels);
        var soulNumbersSum = SumSoulNumbers(soulNumbers);
        console.log(name +" = "+JSON.stringify(soulNumbers)+" = "+soulNumbersSum);
        resultSoulNumbers.push(soulNumbersSum);
        result["fullName"]["names"][name] = {};
        result["fullName"]["names"][name]["vowels"] = {"numbers":soulNumbers,"sum":soulNumbersSum};
    }
    var fullNameSoulNumberVowel = SumSoulNumbers(resultSoulNumbers);
    console.log(fullNameSoulNumberVowel);
    result["fullName"]["vowels"]["numbers"] = resultSoulNumbers;
    result["fullName"]["vowels"]["result"] = fullNameSoulNumberVowel;
    

    console.log("Consonants");
    resultSoulNumbers = [];
    for (var i = 0; i < names.length; i++)
    {
        var name = names[i];
        var soulNumbers = GetSoulNumbers(name, soulSumMode.Consonants);
        var soulNumbersSum = SumSoulNumbers(soulNumbers);
        console.log(name +" = "+JSON.stringify(soulNumbers)+" = "+soulNumbersSum);
        resultSoulNumbers.push(soulNumbersSum);
        result["fullName"]["names"][name]["consonants"] = {"numbers":soulNumbers,"sum":soulNumbersSum};
    }
    var fullNameSoulNumberConsonant = SumSoulNumbers(resultSoulNumbers);
    console.log(fullNameSoulNumberConsonant);
    result["fullName"]["consonants"]["numbers"] = resultSoulNumbers;
    result["fullName"]["consonants"]["result"] = fullNameSoulNumberConsonant;


    console.log("Destiny");
    resultSoulNumbers = [];
    for (var i = 0; i < names.length; i++)
    {
        var name = names[i];
        var soulNumbers = GetSoulNumbers(name, soulSumMode.Everything);
        var soulNumbersSum = SumSoulNumbers(soulNumbers);
        console.log(name +" = "+JSON.stringify(soulNumbers)+" = "+soulNumbersSum);
        resultSoulNumbers.push(soulNumbersSum);
        result["fullName"]["names"][name]["all"] = {"numbers":soulNumbers,"sum":soulNumbersSum};
    }
    var destinySoulNumber = SumSoulNumbers(resultSoulNumbers);
    console.log(destinySoulNumber);
    result["fullName"]["all"]["numbers"] = resultSoulNumbers;
    result["fullName"]["all"]["result"] = destinySoulNumber;
    result["calculations"]["destiny"] = {"numbers": resultSoulNumbers,"result":destinySoulNumber};

    
    console.log("Life Lesson");
    var dob = GetDOB();
    var dates = dob.split("/");
    resultSoulNumbers = [];
    for (var i = 0; i < dates.length; i++)
    {
        var date = dates[i];
        var soulNumbers = GetDigits(date);
        var soulNumbersSum = SumSoulNumbers(soulNumbers);
        console.log(date +" = "+JSON.stringify(soulNumbers)+" = "+soulNumbersSum);
        resultSoulNumbers.push(soulNumbersSum);
        
        result["dob"][date] = {"numbers":soulNumbers,"sum":soulNumbersSum};
        result["dob"]["dmy-"+i.toString()] = {"numbers":soulNumbers,"sum":soulNumbersSum, "date":date};
    }
    var lifeLessonSoulNumber = SumSoulNumbers(resultSoulNumbers);
    console.log(lifeLessonSoulNumber);
    result["calculations"]["lifeLesson"] = {"numbers": resultSoulNumbers, "result":lifeLessonSoulNumber};


    console.log("Powerful Number");
    var destiny_lifeLesson = [destinySoulNumber,lifeLessonSoulNumber];
    resultSoulNumbers = [];
    for (var i = 0; i < destiny_lifeLesson.length; i++)
    {
        var destiny_lifeLesson_num = destiny_lifeLesson[i];
        //var soulNumbers = GetDigits(destiny_lifeLesson_num);
        var soulNumbersSum = SumSoulNumbers([destiny_lifeLesson_num]);
        console.log(destiny_lifeLesson_num + " = "+soulNumbersSum);
        resultSoulNumbers.push(soulNumbersSum);
    }
    var powerfulNumberSoulNumber = SumSoulNumbers(resultSoulNumbers);
    console.log(powerfulNumberSoulNumber);
    result["calculations"]["powerfulNumber"] = {"numbers": resultSoulNumbers, "result":powerfulNumberSoulNumber};
    
    console.log("Life Challenge");
    resultSoulNumbers = [];
    console.log(GetDOB());
    var dobDaySum = SumSoulNumbers([GetDOBDay()]);
    var dobMonthSum = SumSoulNumbers([GetDOBMonth()]);
    var dobYearSum = SumSoulNumbers([GetDOBYear()]);
    //var challenge_1 = Math.abs(dobDaySum-dobMonthSum);
    //var challenge_2 = Math.abs(dobDaySum-dobYearSum);
    //var challenge_3 = Math.abs(challenge_1-challenge_2);
    //var challenge_4 = Math.abs(dobMonthSum-dobYearSum);
    //var lifeChallengeSoulNumber = SumSoulNumbers(resultSoulNumbers);
    //console.log(powerfulNumberSoulNumber);
    //console.log("Challenge 1: "+dobDaySum+" - "+dobMonthSum+" = "+challenge_1);
    //console.log("Challenge 2: "+dobDaySum+" - "+dobYearSum+" = "+challenge_2);
    //console.log("Challenge 3: "+challenge_1+" - "+challenge_2+" = "+challenge_3);
    //console.log("Challenge 4: "+dobMonthSum+" - "+dobYearSum+" = "+challenge_4);

    var challengeDayValue = SumSoulNumbers(GetDigits(result["calculations"]["lifeLesson"]["numbers"][0]));
    var challengeMonthValue = SumSoulNumbers(GetDigits(result["calculations"]["lifeLesson"]["numbers"][1]));
    var challengeYearValue = SumSoulNumbers(GetDigits(result["calculations"]["lifeLesson"]["numbers"][2]));

    var newChallenge1 = Math.abs(challengeDayValue - challengeMonthValue);
    var newChallenge2 = Math.abs(challengeDayValue - challengeYearValue);
    var newChallenge3 = Math.abs(newChallenge1-newChallenge2);
    var newChallenge4 = Math.abs(challengeMonthValue - challengeYearValue);

    result["calculations"]["lifeChallenges"] = {"challenge_1": newChallenge1,"challenge_2": newChallenge2,"challenge_3": newChallenge3,"challenge_4": newChallenge4};
    result["calculations"]["lifeChallenges"]["primaryChallenge"] = newChallenge3;
    result["calculations"]["lifeChallenges"]["secondaryChallenge"] = newChallenge4;
    console.log("RESULT");
    console.log("Primary Challenge: " + newChallenge3);
    console.log("Secondary Challenge: " + newChallenge4);
    console.log("Personal Year");
    var year = GetMYYear();
    console.log("Year = "+year);
    var myYearSum = SumSoulNumbers([GetMYYear()]);
    var personalYearVal = SumSoulNumbers([dobDaySum+dobMonthSum+myYearSum]);
    console.log(dobDaySum+" + "+dobMonthSum+" + "+myYearSum+ " = "+personalYearVal);
    
    console.log("Personal Month");
    var month = GetMyMonth();
    console.log("Month = "+month);
    var soulMonth = SumSoulNumbers(month);
    var personalMonthVal = SumSoulNumbers([month+personalYearVal]);

    console.log(month + " + "+personalYearVal + " = " +personalMonthVal);

    console.log("Personal Day");
    var day = GetMyDay();
    var soulDay = SumSoulNumbers([day]);
    console.log("Day = "+day + " = " + soulDay);

    var soulDayVal = SumSoulNumbers([day,month,personalYearVal]);
    console.log(day+" + "+month+" + "+personalYearVal + " = "+soulDayVal);

    console.log("Personal Day = "+soulDayVal);
    console.log("Personal Month = "+personalMonthVal);
    console.log("Personal Year = "+personalYearVal);

    result["calculations"]["personalDates"]["day"] = soulDayVal;
    result["calculations"]["personalDates"]["month"] = personalMonthVal;
    result["calculations"]["personalDates"]["year"] = personalYearVal;

    result["calculations"]["personalDates"]["day_sum_array"] = [day,month,personalYearVal];
    result["calculations"]["personalDates"]["month_sum_array"] = [month,personalYearVal];
    result["calculations"]["personalDates"]["year_sum_array"] = [dobDaySum,dobMonthSum,myYearSum];

    console.log("Pináculos/Fases da vida (Phase of Life)");

    var phase1 = 36 - lifeLessonSoulNumber;
    var phase1Val = SumSoulNumbers([dobDaySum,dobMonthSum]);
    console.log("Phase 1 (0 - "+phase1+" Years) = " + phase1Val);
    var phase2 = phase1+1;
    var phase2EndYear = phase1+9;
    var phase2Val = SumSoulNumbers([dobDaySum,SumSoulNumbers(GetDigits(GetDOBYear()))]);
    console.log("Phase 2 ("+phase2+" - "+phase2EndYear+" Years) = " + phase2Val);

    var phase3 = phase2EndYear+1;
    var phase3EndYear = phase2EndYear+9;
    var phase3Val = SumSoulNumbers([phase1Val, phase2Val]);
    console.log("Phase 3 ("+phase3+" - "+phase3EndYear+" Years) = " + phase3Val);
    
    var phase4 = phase3EndYear+1;
    var phase4Val = SumSoulNumbers([dobMonthSum, dobYearSum]);
    console.log("Phase 4 ("+phase4+"+ Years) = " + phase4Val);

    
    result["calculations"]["phasesOfLife"]["phase1"] = {"from": 0, "to":phase1, "result": phase1Val};
    result["calculations"]["phasesOfLife"]["phase2"] = {"from": phase2, "to":phase2EndYear, "result": phase2Val};
    result["calculations"]["phasesOfLife"]["phase3"] = {"from": phase3, "to":phase3EndYear, "result": phase3Val};
    result["calculations"]["phasesOfLife"]["phase4"] = {"from": phase4, "to":null, "result": phase4Val};

    //var houseNumberVal = ;
    var houseDigits = GetDigits(GetHouseNumber());
    console.log("House Number = "+JSON.stringify(houseDigits));
    console.log("This should be 11+3!!!");
    var professionalName = destinySoulNumber;
    resultSoulNumbers = [];
    var professionalNames = GetProfessionalName().split(" ");
    for (var i = 0; i < professionalNames.length; i++)
    {
        var name = professionalNames[i];
        var soulNumbers = GetSoulNumbers(name, soulSumMode.Everything);
        var soulNumbersSum = SumSoulNumbers(soulNumbers);
        //console.log(name +" = "+soulNumbersSum);
        resultSoulNumbers.push(soulNumbersSum);
    }
    var professionalName = SumSoulNumbers(resultSoulNumbers);
    console.log("Professional Name = "+JSON.stringify(resultSoulNumbers)+" = " +professionalName);
    result["calculations"]["professionalName"] = {"numbers": resultSoulNumbers, "result":professionalName};


    return result;
}

function DoCalculations()
{
    var result = GetResults();
    console.log(result);
    PopuplateResults(result);
    $("#staticBackdrop").modal("hide");
    $("#calculationResult").modal("show");
}

function PopuplateResults(result)
{
    PopulateResultsName(result);
    PopulateResultsNameConsonates(result);
    PopulateDestiny(result);
    PopulateLifeLesson(result);
    PopulatePowerfulNumber(result);
    PopulateLifeChallenges(result);
    PopulatePersonalYear(result);
    PopulatePersonalMonth(result);
    PopulatePersonalDay(result);
    PopulatePhasesOfLife(result);
    PopulateResultsDisplayInputs()
}

function PopulateResultsDisplayInputs() {

    $("#result_display_name").html(GetFullName());
    $("#result_display_birth").html(GetDOBLocal());
    $("#result_display_address").html(BreakHouseNumberDown());
    $("#result_display_prof_name").html(GetProfessionalName());
}


function PopulatePersonalYear(result) {

    
    var html = `<li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Ano: `+result.calculations.personalDates.year+`</div>
        <ul class="list-group">
            <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(result.calculations.personalDates.year_sum_array)+`<span class="badge bg-secondary rounded-pill float-end">`+result.calculations.personalDates.year+`</li>
        </ul>
    </div>
   </li>`;
   
    html += "<h4 class='p-2 text-bg-primary'>Ano Pessoal = "+result.calculations.personalDates.year+"</h4>";
   
    $("#resultsAnoPessoal").html(html);
    $("#finalResultAnoPessoal").html(result.calculations.personalDates.year);
   }

   

function PopulatePersonalMonth(result) {

    
    var html = `<li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Mês: `+result.calculations.personalDates.month+`</div>
        <ul class="list-group">
            <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(result.calculations.personalDates.month_sum_array)+`<span class="badge bg-secondary rounded-pill float-end">`+result.calculations.personalDates.month+`</li>
        </ul>
    </div>
   </li>`;
   
    html += "<h4 class='p-2 text-bg-primary'>Ano Pessoal = "+result.calculations.personalDates.month+"</h4>";
   
    $("#resultsMesPessoal").html(html);
    $("#finalResultMesPessoal").html(result.calculations.personalDates.month);
   }
   

function PopulatePersonalDay(result) {

    
    var html = `<li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Dia: `+result.calculations.personalDates.day+`</div>
        <ul class="list-group">
            <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(result.calculations.personalDates.day_sum_array)+`<span class="badge bg-secondary rounded-pill float-end">`+result.calculations.personalDates.day+`</li>
        </ul>
    </div>
   </li>`;
   
    html += "<h4 class='p-2 text-bg-primary'>Ano Pessoal = "+result.calculations.personalDates.day+"</h4>";
   
    $("#resultsDiaPessoal").html(html);
    $("#finalResultDiaPessoal").html(result.calculations.personalDates.day);
   }

function PopulateLifeLesson(result) {

    
 var html = `<li class="list-group-item d-flex justify-content-between align-items-start">
 <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
   <div class="fw-bold">Dia: `+result.dob["dmy-0"].date+`</div>
     <ul class="list-group">
         <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(result.dob["dmy-0"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+result.dob["dmy-0"]["sum"]+`</li>
     </ul>
 </div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-start">
 <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
   <div class="fw-bold">Mês: `+result.dob["dmy-1"].date+`</div>
     <ul class="list-group">
         <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(result.dob["dmy-1"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+result.dob["dmy-1"]["sum"]+`</li>
     </ul>
 </div>
</li>
<li class="list-group-item d-flex justify-content-between align-items-start">
 <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
   <div class="fw-bold">Ano: `+result.dob["dmy-2"].date+`</div>
     <ul class="list-group">
         <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(result.dob["dmy-2"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+result.dob["dmy-2"]["sum"]+`</li>
     </ul>
 </div>
</li>`;

 html += "<h4 class='p-2 text-bg-primary'>Lição de vida = "+result.calculations.lifeLesson.result+"</h4>";

 $("#resultsLifeLessons").html(html);
 $("#finalResultLifeLessons").html(result.calculations.lifeLesson.result);
}

function PopulateDestiny(result) {



 var html = "";
 var names = Object.keys(result.fullName.names);
 for (let index = 0; index < names.length; index++) {
     var name = names[index];
     var data = result.fullName.names[name];
     console.log(data);
     var element = `<li class="list-group-item d-flex justify-content-between align-items-start">
 <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
   <div class="fw-bold">`+name+`</div>
     <ul class="list-group">
         <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(data["all"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+data["all"]["sum"]+`</li>
     </ul>
 </div>
</li>`;

     html+=element;
 }

 html += "<h4 class='p-2 text-bg-primary'>Destino = "+result.fullName.all.result+"</h4>";

 $("#resultsDestiny").html(html);
 $("#finalResultDestiny").html(result.fullName.all.result);
}

function PopulatePhasesOfLife(result) {
    
    var html = `<ol class="list-group list-group-numbered" >
    <li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Fase 1 - até os `+result["calculations"]["phasesOfLife"]["phase1"]["to"]+` anos</div>
    </div>
    <span class="badge bg-primary rounded-pill">`+result["calculations"]["phasesOfLife"]["phase1"]["result"]+`</span>
  </li>
  <li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Fase 2 - dos `+result["calculations"]["phasesOfLife"]["phase2"]["from"]+` aos `+result["calculations"]["phasesOfLife"]["phase2"]["to"]+` anos</div>
    </div>
    <span class="badge bg-primary rounded-pill">`+result["calculations"]["phasesOfLife"]["phase2"]["result"]+`</span>
  </li>
  <li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Fase 3 - dos `+result["calculations"]["phasesOfLife"]["phase3"]["from"]+` aos `+result["calculations"]["phasesOfLife"]["phase3"]["to"]+` anos</div>
    </div>
    <span class="badge bg-primary rounded-pill">`+result["calculations"]["phasesOfLife"]["phase3"]["result"]+`</span>
  </li>
  <li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Fase 4 - acima dos `+result["calculations"]["phasesOfLife"]["phase4"]["from"]+` anos</div>
    </div>
    <span class="badge bg-primary rounded-pill">`+result["calculations"]["phasesOfLife"]["phase4"]["result"]+`</span>
  </li>       
  </ol>`;
    
  $("#resultsPhasesOfLife").html(html);
}

function PopulateLifeChallenges(result) {

    var inner = `<li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Primeiro Desafio</div>
    </div>
    <span class="badge bg-secondary rounded-pill">`+result["calculations"]["lifeChallenges"]["primaryChallenge"]+`</span>
  </li>`;
if (result["calculations"]["lifeChallenges"]["primaryChallenge"] != result["calculations"]["lifeChallenges"]["secondaryChallenge"])
{
    
  inner += `<li class="list-group-item d-flex justify-content-between align-items-start">
  <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
    <div class="fw-bold">Segundo Desafio</div>
  </div>
  <span class="badge bg-secondary rounded-pill">`+result["calculations"]["lifeChallenges"]["secondaryChallenge"]+`</span>
</li>   `;

}

    var html = `<ol class="list-group list-group-numbered" >
    `+inner+`     
  </ol>`;
    
    $("#resultsLifeChallenges").html(html);
}

function PopulatePowerfulNumber(result){
    var html = `
    <ol class="list-group list-group-numbered" >
    <li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Destino</div>
    </div>
    <span class="badge bg-secondary rounded-pill">`+result["calculations"]["destiny"]["result"]+`</span>
  </li>
  <li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">Lição de vida</div>
    </div>
    <span class="badge bg-secondary rounded-pill">`+result["calculations"]["lifeLesson"]["result"]+`</span>
  </li>
            
  </ol>`;
  html += "<h4 class='p-2 text-bg-primary'>Número poderoso = "+result["calculations"]["powerfulNumber"]["result"]+"</h4>";
    
    $("#resultsPowerfulNumber").html(html);
    $("#finalResultPowerfulNumber").html(result["calculations"]["powerfulNumber"]["result"]);
    
}
function PopulateResultsName(result){
    var html = "";
    var names = Object.keys(result.fullName.names);
    for (let index = 0; index < names.length; index++) {
        var name = names[index];
        var data = result.fullName.names[name];
        console.log(data);
        var element = `<li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">`+name+`</div>
        <ul class="list-group">
            <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(data["vowels"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+data["vowels"]["sum"]+`</span></li>
            <!--<li class="list-group-item"><strong>Consonants</strong> `+JSON.stringify(data["consonants"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+data["consonants"]["sum"]+`</li>
            <li class="list-group-item"><strong>All</strong> `+JSON.stringify(data["all"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+data["all"]["sum"]+`</li>-->
        </ul>
    </div>
  </li>`;

        html+=element;
    }

    html += "<h4 class='p-2 text-bg-primary'>Alma = "+result.fullName.vowels.result+"</h4>";

    $("#resultsName").html(html);
    $("#finalResultName").html(result.fullName.vowels.result);
    
}

function PopulateResultsNameConsonates(result){
    var html = "";
    var names = Object.keys(result.fullName.names);
    for (let index = 0; index < names.length; index++) {
        var name = names[index];
        var data = result.fullName.names[name];
        console.log(data);
        var element = `<li class="list-group-item d-flex justify-content-between align-items-start">
    <div class="ms-2 me-auto" style="width: -webkit-fill-available;">
      <div class="fw-bold">`+name+`</div>
        <ul class="list-group">
            
            <li class="list-group-item"><strong>Números</strong> `+JSON.stringify(data["consonants"]["numbers"])+`<span class="badge bg-secondary rounded-pill float-end">`+data["consonants"]["sum"]+`</li>
        </ul>
    </div>
  </li>`;

        html+=element;
    }

    html += "<h4 class='p-2 text-bg-primary'>Aparência = "+result.fullName.consonants.result+"</h4>";

    $("#resultsNameCon").html(html);
    $("#finalResultNameCon").html(result.fullName.consonants.result);
    
}

function BreakHouseNumberDown() {
    var houseNumber = GetHouseNumber();
    var houseDigits = GetDigits(houseNumber);
    var houseSum = SumSoulNumbers(houseDigits);
    
    console.log("House Number Sum: " + houseSum);
    return houseSum;
}

function SoulNumNeedsRecalculated(num)
{
    return (num > 9 && num != 11 && num != 22 && num != 33);
}

function SumSoulNumbers(nums)
{
    var num = 0;
    for (var i = 0; i < nums.length; i++)
    {
        num += nums[i];
    }

    var needsRecalculated = SoulNumNeedsRecalculated(num);

    while (needsRecalculated)
    {
            var digits = GetDigits(num);
            var newNum = 0;
            for (var i = 0; i < digits.length; i++)
            {
                var int = digits[i];
                newNum += int;
            }

            num = newNum;
            needsRecalculated = SoulNumNeedsRecalculated(num);
    }

    return num;
}

function GetDigits(num)
{

    var numStr = num.toString();
    var ints = [];
    for (var i = 0; i< numStr.length; i++)
    {
        var digit = numStr[i];
        ints.push(parseInt(digit));
    }

    return ints;
}

function monthName(monthNumber) {
    let months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    return months[parseInt(monthNumber, 10) - 1];
}

function hasDatePassed(dateStr) {
    let parts = dateStr.split("/");
    let givenDate = new Date(parts[2], parts[1] - 1, parts[0]);
    let currentDate = new Date();
    currentDate.setHours(0, 0, 0, 0);  // To ensure we compare dates only, not time
    return givenDate < currentDate;
}

function GetCurrentYear()
{
    return  new Date().getFullYear();
}
function GetCurrentMonth()
{
    return  new Date().getMonth()+1;
}
function GetCurrentDay()
{
    return  new Date().getDate();
}

function GetMYYear()
{
    var yearStr = GetCurrentYear();
    var yearInt = parseInt(yearStr);
    var thisYearBirthday = GetDOBDay()+"/"+GetDOBMonth()+"/"+yearStr;
    if (hasDatePassed(thisYearBirthday))
    {
        return yearInt; 
    }
    else
    {
        return yearInt-1;
    }
}

function GetMyMonth()
{
    var monthStr = GetCurrentMonth();
    var monthInt = parseInt(monthStr);


    return monthInt;
}


function GetMyDay()
{
    var str = GetCurrentDay();
    var int = parseInt(str);


    return int;
}