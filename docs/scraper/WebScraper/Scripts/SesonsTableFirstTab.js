function GetSesonsTableFirstTabSecondAproach() {
    let data = document.querySelectorAll('#main-col > div.maincontent > div.row > div.col-sm-9.col-lg-10 > table > tbody tr');

    let round = {};
    let arr = [];
    let detailList = [];

    for (let i = 0; data.length; i++) {
        if (i >= data.length) {
            round.Details = detailList;
            arr.push(round);
            break
        }
        if (data[i] !== undefined && data[i + 1] !== undefined && data[i + 2] !== undefined) {
            if (data[i].className === '' && data[i + 1].className === '' && data[i + 2].className === '') {
                if (Object.keys(round).length > 0) {
                    round.Details = detailList;
                    arr.push(round);
                    round = {};
                    detailList = [];
                }
                round.Round = data[i].innerText;
                round.Date = data[i + 1].innerText;
                round.Organiser = data[i + 2].innerText;
                i += 2;
            }
            else if (data[i].className === '' && data[i + 1].className !== '') {
                round.Details = detailList;
                arr.push(round);
                let temp = { Round: round.Round, Date: round.Date }
                round = {}
                round.Round = temp.Round;
                round.Organiser = data[i].innerText;
                round.Date = temp.Date;
                detailList = [];
            }
            else {
                try {
                    let cols = data[i].querySelectorAll('td');
                    let linkVal = '';
                    if (cols[8] !== undefined) {
                        linkVal = cols[8].querySelector('a').href
                    }
                    let details = {
                        p1: cols[1].innerText,
                        p2: cols[3].innerText,
                        Score: cols[7] !== undefined ? cols[7].innerText : "",
                        Link: linkVal
                    };
                    detailList.push(details);
                } catch (error) {
                }
            }
        }
    }
    return arr;
}