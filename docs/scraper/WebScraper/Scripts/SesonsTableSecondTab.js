function SesonsTableSecondTab(selector) {
    //let rows = document.querySelectorAll('#main-col > div.maincontent > div > div.col-sm-9.col-lg-10 > div > div > table.table.table-condensed.table-striped tr');
    let rows = document.querySelectorAll(selector);
    let result = [];
    let team = {};
    let resultPerTeam = [];
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].getElementsByTagName("b").length == 1) {
            team.Name = rows[i].getElementsByTagName("b")[0].innerText;
        }
        else if (rows[i].firstElementChild.className === '' && rows[i].firstElementChild.style.borderTop == '' && rows[i].childElementCount >= 2) {
            let result = {
                Score: rows[i].cells[0].innerText,
                Name: rows[i].cells[1].innerText
            }
            resultPerTeam.push(result)
        }
        else {
            if (resultPerTeam.length > 0) {
                team.Details = resultPerTeam;
                team.FinalResult = rows[i].cells[0].innerText
                result.push(team);
                team = {};
                resultPerTeam = [];
            }
        }
    }
    return result;
}