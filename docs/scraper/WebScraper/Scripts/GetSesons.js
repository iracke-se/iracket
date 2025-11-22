function GetSesons(selector) {
    let rows = document.querySelectorAll(selector);
    if (rows.length == 0) {
        rows = document.querySelectorAll('#main-col > div.maincontent > div.row > div.col-sm-3.col-lg-2 > div > div > div.navbar-collapse.collapse.sidebar-navbar-collapse > ul li');
    }
    let ar = new Array();
    let coData = { Parts: [] };
    for (let i = 0; i < rows.length; i++) {
        if (rows[i].className === 'divisjon') {
            if (coData.Title === undefined) {
                coData.Title = rows[i].innerText;
            }
            else {
                ar.push(coData);
                coData = { Parts: [] }
                    ; coData.Title = rows[i].innerText;
            }
        }
        if (rows[i].className === 'enavd') {
            coData.Parts.push({ Label: rows[i].innerText, Link: rows[i].firstElementChild.href });
        }
        if (rows[i].className === 'omraade dropdown') {
            var dropdowns = rows[i].getElementsByTagName('a');
            coData.Parts.push({ Label: rows[i].innerText, Link: '' });
            let dropdownElements = [];
            for (let j = 0; j < dropdowns.length; j++) {
                if (dropdowns[j].className !== 'dropdown-toggle') {
                    dropdownElements.push({ Label: dropdowns[j].innerText, Link: dropdowns[j].href });
                }
                else {
                    dropdowns[j].setAttribute("selector", dropdowns[j].innerText + coData.Title);
                }
            }
            coData.Parts[coData.Parts.length - 1].Nested = dropdownElements;
        }
    }
    return ar;
}