function GetSesonsTableFirstTab(selector) {
    var mathcer = document.querySelectorAll(selector);
    let mathcerObj = { Details: [] };
    let mathcherArr = new Array();
    for (let i = 0; i < mathcer.length; i++) {
        if (mathcer[i].childElementCount === 1) {
            if (mathcerObj.Title !== undefined && mathcerObj.Title !== '') {
                mathcherArr.push(mathcerObj);
            }
            mathcerObj = { Details: [] };
            mathcerObj.Title = mathcer[i].querySelector("td").innerText;
        }
        else {
            var allCols = mathcer[i].querySelectorAll("td");
            let matchDetails = {};
            matchDetails.Date = allCols[0].innerText;
            matchDetails.Time = allCols[1].innerText;
            matchDetails.Player1 = allCols[2].innerText;
            matchDetails.Player2 = allCols[4].innerText;
            if (allCols[8] !== undefined)
                matchDetails.Score = allCols[8].innerText;
            if (allCols[9] !== undefined)
                matchDetails.Link = allCols[9].firstElementChild.href;
            mathcerObj.Details.push(matchDetails);
        }
    }
    return mathcherArr;
}