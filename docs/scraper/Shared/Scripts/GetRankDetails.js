function GetRankDetails(selector) {
    //var rows = document.querySelectorAll('#multipurpose > table > tbody tr');
    var rows = document.querySelectorAll(selector);

    let licenc = "";

    var selector = document.querySelector('b');

    if (selector) {
        licenc = selector.innerText.split('\n')[1]
    }

    let finalData = {
        Licenc: licenc
    }
    let result = [];
    for (var i = 1; i < rows.length; i++) {

        let data = {
            Date: rows[i].cells[0].innerText,
            Point: rows[i].cells[1].innerText,
            Location: rows[i].cells[2].innerText,
            PointDifference: rows[i].cells[3].innerText,
            PointId : rows[i].cells[1].firstElementChild.id
        };
        result.push(data);
    }
    finalData.Details = result;
    return finalData;
}