function GetRanks(selector) {
    //var rows = document.querySelectorAll('#main-col > div.maincontent > table.table.table-condensed.table-hover.table-striped > tbody tr');
    var rows = document.querySelectorAll(selector);
    let result = [];
    for (var i = 1; i < rows.length; i++) {
        let data = {
            Investment: rows[i].cells[0].innerText + ' ' + rows[i].cells[1].innerText,
            Name: rows[i].cells[2].innerText,
            Born: rows[i].cells[3].innerText,
            Club: rows[i].cells[4].innerText,
            Point: rows[i].cells[5].innerText + ' ' + rows[i].cells[6].innerText,
        };
        result.push(data);
    }
    return result;
}