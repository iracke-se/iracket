(function () {
    var rows = document.getElementsByName('--NAMEPLACEHOLDER--')[0].getElementsByTagName('option');
    let data = [];
    for (var i = 0; i < rows.length; i++) {
        let element = {
            Text: rows[i].text,
            Value: rows[i].value,
        }
        data.push(element);
    }
    return data;
})()