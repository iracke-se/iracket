function GetDivisionData(path) {
    let rows = document.querySelectorAll(path);
    let ar = new Array();
    for (let r = 0; r < rows.length; r++) {
        let rowData = { Id: rows[r].id };
        let columns = rows[r].querySelectorAll('td');
        let coData = { Name: '', Score: '' };
        for (let i = 0; i < columns.length - 1; i++) {
            if (i === 0)
                coData.Name = columns[i].innerHTML;
            if (i === 1)
                coData.Score = columns[i].innerHTML;
        }
        rowData.values = coData; ar.push(rowData);
    }
    return ar;
}

function GetMatchData(path) {
    let rows = document.querySelectorAll(path);
    let ar = new Array();
    for (let r = 0; r < rows.length; r++) {
        let columns = rows[r].querySelectorAll('td');
        let coData = { Id: rows[r].id, Score: '' };
        for (let i = 0; i < columns.length; i++) {
            if (i === 0)
                coData.Part = columns[i].innerHTML;
            if (i === 1)
                coData.Plauer1 = columns[i].innerHTML;
            if (i === 3)
                coData.Plauer2 = columns[i].innerHTML;
            if (i === 4)
                coData.Score = columns[i].innerHTML;
        }
        ar.push(coData);
    }
    return ar;
}

function GetMatchDetailsData(path) {
    let rows = document.querySelectorAll(path);
    let ar = new Array();
    for (let r = 0; r < rows.length; r++) {
        let columns = rows[r].querySelectorAll('td');
        for (let i = 0; i < columns.length; i++) {
            let coData = {};
            coData.setNumber = i + 1
            coData.Score = columns[i].innerHTML;
            coData.Id = columns[i].id;
            ar.push(coData);
        }
    }
    return ar;
}

function GetMatchScoresData(path) {
    let rows = document.querySelectorAll(path);
    let ar = new Array();
    for (let r = 2; r < rows.length; r++) {
        let columns = rows[r].querySelectorAll('td');
        let coData = {};
        for (let i = 0; i < columns.length; i++) {
            if (i === 0)
                coData.Score = columns[i].innerHTML;
            if (i === 2)
                coData.Serve = columns[i].innerHTML;
            if (i === 4)
                coData.Comment = columns[i].innerHTML;
        }
        ar.push(coData);
    }
    return ar;
}