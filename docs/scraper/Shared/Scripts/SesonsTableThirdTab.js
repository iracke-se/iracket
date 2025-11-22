function SesonsTableThirdTab(selector) {
    //let rows = document.querySelectorAll('#main-col > div.maincontent > div > div.col-sm-9.col-lg-10 > div > div > table.table.table-condensed.table-striped tr');
    let rows = document.querySelectorAll(selector);
    let results = [];
    for (var i = 0; i < rows.length; i++) {
        let player = {
            Score: rows[i].cells[0].innerText,
            Name: rows[i].cells[1].innerText,
            Team: rows[i].cells[2].innerText,
        };
        results.push(player);
    }
    return results;
}