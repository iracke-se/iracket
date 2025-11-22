function GetSesonsTable(selector) {
    let rows = document.querySelectorAll(selector);
    let ar = new Array();
    for (let i = 1; i < rows.length; i++) {
        let data = {
            Lag: rows[i].children[0].innerText,
            M: rows[i].children[1].innerText,
            P3: rows[i].children[2].innerText,
            P2: rows[i].children[3].innerText,
            P1: rows[i].children[4].innerText,
            OP: rows[i].children[5].innerText,
            Matcher: rows[i].children[6].innerText,
            Set: rows[i].children[7].innerText,
            Balls: rows[i].children[8].innerText,
            Point: rows[i].children[9].innerText
        }
        ar.push(data);
    }
    return ar;
}
function GetSesonsTableV2(selector) {
    let rows = document.querySelectorAll(selector);
    let ar = new Array();
    for (let i = 1; i < rows.length; i++) {
        let data = {
            Lag: rows[i].children[0].innerText,
            M: rows[i].children[1].innerText,
            V: rows[i].children[2].innerText,
            O: rows[i].children[3].innerText,
            F: rows[i].children[4].innerText,
            Matcher: rows[i].children[5].innerText,
            Set: rows[i].children[6].innerText,
            Balls: rows[i].children[7].innerText,
            Point: rows[i].children[8].innerText
        }
        ar.push(data);
    }
    return ar;
}

function IsItFirstAproach(selector) {
    let rows = document.querySelectorAll(selector);

    if (rows.length > 0) {
        let cols = rows[0].querySelectorAll('td');
        if (cols.length > 9) {
            return true;
        }
        else {
            return false;
        }
    }
    return null;
}