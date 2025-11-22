function GetMatcherDetails() {
    let rows = document.querySelectorAll("#div_kamplive > table > tbody > tr");
    let result = [];

    let headers = [...rows[0].cells];
    let sets = headers.filter(function (a) {
        if (a.innerText.startsWith('Set')) {
            return true
        }

    });

    for (let i = 1; i < rows.length; i++) {
        let cols = rows[i].cells;

        if (sets.length == 3) {
            if (cols.length > 2) {
                let data = {
                    Ser: cols[0].innerText,
                    Des1: cols[1].innerText,
                    Name1: [cols[2].innerText],
                    Des2: cols[3].innerText,
                    Name2: [cols[4].innerText],
                    Set1: cols[5].innerText,
                    Set2: cols[6].innerText,
                    Set3: cols[7].innerText,
                    Score: cols[8].innerText,
                };
                result.push(data);
            }
            else {
                var lastItem = result.pop();
                lastItem.Name1.push(cols[0].innerText);
                lastItem.Name2.push(cols[1].innerText);
                result.push(lastItem);
            }
        }
        else if (sets.length == 4) {
            if (cols.length > 2) {
                let data = {
                    Ser: cols[0].innerText,
                    Des1: cols[1].innerText,
                    Name1: [cols[2].innerText],
                    Des2: cols[3].innerText,
                    Name2: [cols[4].innerText],
                    Set1: cols[5].innerText,
                    Set2: cols[6].innerText,
                    Set3: cols[7].innerText,
                    Set4: cols[8].innerText,
                    Score: cols[9].innerText,
                };
                result.push(data);
            }
            else {
                var lastItem = result.pop();
                lastItem.Name1.push(cols[0].innerText);
                lastItem.Name2.push(cols[1].innerText);
                result.push(lastItem);
            }
        }
        else if (sets.length == 5) {
            if (cols.length > 2) {
                let data = {
                    Ser: cols[0].innerText,
                    Des1: cols[1].innerText,
                    Name1: [cols[2].innerText],
                    Des2: cols[3].innerText,
                    Name2: [cols[4].innerText],
                    Set1: cols[5].innerText,
                    Set2: cols[6].innerText,
                    Set3: cols[7].innerText,
                    Set4: cols[8].innerText,
                    Set5: cols[9].innerText,
                    Score: cols[10].innerText,
                };
                result.push(data);
            }
            else {
                var lastItem = result.pop();
                lastItem.Name1.push(cols[0].innerText);
                lastItem.Name2.push(cols[1].innerText);
                result.push(lastItem);
            }
        }


    }
    return result;
}
