function GetTransitions(selector) {
    let rows = document.querySelectorAll(selector);
    //let rows = document.querySelectorAll("#main-col > div.maincontent > form > table > tbody > tr");
    let Results = [];
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].className !== "tabellhode") {
            let data =
            {
                Surname: rows[i].cells[0].innerText,
                FirstName: rows[i].cells[1].innerText,
                Born: rows[i].cells[2].innerText,
                From: rows[i].cells[3].innerText,
                To: rows[i].cells[4].innerText,
                GameCompletionDate: rows[i].cells[5].innerText,
            };
            Results.push(data);
        }
    }

    return Results;
}