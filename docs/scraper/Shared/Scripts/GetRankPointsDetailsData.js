
function GetRankPointsDetails(selector) {
    var rows = document.querySelectorAll(selector);

    //var rows = document.querySelectorAll('#multipurpose > table > tbody tr');
    let breakIndex = 0;
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].childElementCount === 1) {
            breakIndex = i;
            break;
        }
    }
    finalData = {};

    if (breakIndex === 3) {
        finalData.ExPoints = rows[0].cells[2].innerText,
        finalData.Adjustment = rows[1].cells[2].innerText,
        finalData.ExPointsAdjusted = rows[2].cells[2].innerText
    }
    if (breakIndex === 1) {
        finalData.ExPoints = rows[0].cells[2].innerText
    }
    
    let result = [];
    for (var i = breakIndex + 1; i < rows.length; i++) {

        if (rows[i].childElementCount === 1 || rows[i].childElementCount === 4) {
            continue;
        }

        let data = {
            Status: rows[i].cells[0].innerText,
            Name: rows[i].cells[1].innerText,
            Place: rows[i].cells[2].innerText,
            MatchPoint: rows[i].cells[3].innerText,
            Date: rows[i].cells[4].innerText,
          
        };
        result.push(data);
    }
    finalData.Details = result;
    return finalData;
}