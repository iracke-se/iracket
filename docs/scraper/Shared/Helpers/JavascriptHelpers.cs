using System;
using System.Collections.Generic;
using System.Text;

namespace Shared.Helpers
{
   public static class JavascriptHelpers
    {

        public static string GetAllValuesFromDropDown(string id)
        {
            return $"(function () {{ \n" +
                 $"var arr = new Array();\n" +
                 $"for(let i=0;i< document.getElementById('{id}').options.length;i++)\n" +
                 $"{{\n" +
                 $"let res = {{ Value:document.getElementById('{id}').options[i].value,Text: document.getElementById('{id}').options[i].innerHTML}}\n" +
                 $"arr.push(res);}}\n" +
                 $"return arr\n" +
                 $"}}());";
        }

        public static string GetAllValuesFromDropDownRanksPeriod()
        {
            var func = System.IO.File.ReadAllText($"Scripts/DropDownByName.js");
            return func.Replace("--NAMEPLACEHOLDER--", "rid");
        }

        public static string GetAllValuesFromDropDownRanksDevisions()
        {
            var func = System.IO.File.ReadAllText($"Scripts/DropDownByName.js");
            return func.Replace("--NAMEPLACEHOLDER--", "distr");
        }


        public static string GetRanksFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetRanks.js");
        } 
        public static string GetRankPointsDetailsFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetRankPointsDetailsData.js");
        }   public static string GetRankDetailsFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetRankDetails.js");
        }

        public static string GetTableData(string tableSelector)
        {
            return $"const rows = document.querySelectorAll('{tableSelector} tr');\n" +
                $"Array.from(rows, row => {{\n" +
                $"const columns = row.querySelectorAll('td');\n" +
                $"return Array.from(columns, column => column.innerText);\n" +
                $"}});";
        }

        public static string DelcatreDivisinFunction(string selector)
        {
            return $"function GetDivisionData (selector) {{\n" +
                $"let rows = document.querySelectorAll(selector);\n" +
                $"let ar = new Array();\n" +
                $"rows.forEach(row => {{\n" +
                $"let rowData =\n" +
                $"{{\n" +
                $"Id: row.id\n" +
                $"}};\n" +
                $"let columns = row.querySelectorAll('td');\n" +
                $"let colVals = new Array();\n" +
                $"columns.forEach(col => {{\n" +
                $"colVals.push(col.innerText);\n" +
                $"}});\n" +
                $"rowData.values = colVals;ar.push(rowData);\n" +
                $"}});\n" +
                $"return ar;\n" +
                $"}};";
        }

        public static string DelcatreDivisinFunctionV2()
        {
            return $"function GetDivisionData(path) {{\n" +
                $"let rows = document.querySelectorAll(path);\n" +
                $"let ar = new Array();\n" +
                $"for (let r = 0; r < rows.length; r++){{\n" +
                $"let rowData ={{Id: rows[r].id}};\n" +
                $"let columns = rows[r].querySelectorAll('td');\n" +
                $"let coData = {{Name: '',Score: ''}};\n" +
                $"for (let i = 0; i<columns.length - 1; i++)\n" +
                $"{{\n" +
                $"if (i === 0)\n" +
                $"coData.Name = columns[i].innerHTML;\n" +
                $"if (i === 1)\n" +
                $"coData.Score = columns[i].innerHTML;\n" +
                $"}}\n" +
                $"rowData.values = coData;ar.push(rowData);\n" +
                $"}}\n" +
                $"return ar;\n" +
                $"}}";
        }

        public static string DivisionDataFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/DivisionData.js");
        }

        public static string DeclareMathcFunction()
        {
            return $"function GetMatchData(path) {{\n" +
                $"let rows = document.querySelectorAll(path);\n" +
                $"let ar = new Array();\n" +
                $"for (let r = 0; r < rows.length; r++){{\n" +
                $"let columns = rows[r].querySelectorAll('td');\n" +
                $"let coData = {{Id: rows[r].id, Score: ''}};\n" +
                $"for (let i = 0; i<columns.length; i++) {{\n" +
                $"if (i === 0)\n" +
                $"coData.Part = columns[i].innerHTML;\n" +
                $"if (i === 1)\n" +
                $"coData.Plauer1 = columns[i].innerHTML;\n" +
                $"if (i === 3)\n" +
                $"coData.Plauer2 = columns[i].innerHTML;\n" +
                $"if (i === 4)\n" +
                $"coData.Score = columns[i].innerHTML;\n" +
                $"}}\n" +
                $"ar.push(coData);\n" +
                $"}}\n" +
                $"return ar;\n" +
                $"}}";
        }

        public static string DeclareMatchDetilsFunction()
        {
            return $"function GetMatchDetailsData(path) {{\n" +
                $"let rows = document.querySelectorAll(path);\n" +
                $"let ar = new Array();\n" +
                $"for (let r = 0; r < rows.length; r++){{\n" +
                $"let columns = rows[r].querySelectorAll('td');\n" +
                $"for (let i = 0; i < columns.length; i++){{\n" +
                $"let coData = {{ }};\n" +
                $"coData.setNumber = i + 1\n" +
                $"coData.Score = columns[i].innerHTML;\n" +
                $"coData.Id = columns[i].id;\n" +
                $"ar.push(coData);\n" +
                $"}}\n" +
                $"}}\n" +
                $"return ar;\n" +
                $"}}";
        }

        public static string GetMathcScoresFunction()
        {
            return $"function GetMatchScoresData(path) {{\n" +
                $"let rows = document.querySelectorAll(path);\n" +
                $"let ar = new Array();\n" +
                $"for (let r = 2; r < rows.length; r++){{\n" +
                $"let columns = rows[r].querySelectorAll('td');\n" +
                $"let coData = {{ }};\n" +
                $"for (let i = 0; i < columns.length; i++){{\n" +
                $"if (i === 0)\n" +
                $"coData.Score = columns[i].innerHTML;\n" +
                $"if (i === 2)\n" +
                $"coData.Serve = columns[i].innerHTML;\n" +
                $"if (i === 4)\n" +
                $"coData.Comment = columns[i].innerHTML;\n" +
                $"}}\n" +
                $"ar.push(coData);\n" +
                $"}}\n" +
                $"return ar;\n" +
                $"}}";
        }

        public static string GetDivisionTableData(string selector)
        {
            return $"GetDivisionData('{selector}')";
        }

        public static string GetMathcTableData(string selector)
        {
            return $"GetMatchData('{selector}')";
        }

        public static string GetMathcDetailsTableData(string selector)
        {
            return $"GetMatchDetailsData('{selector}')";
        }

        public static string GetMathcScoresData(string selector)
        {
            return $"GetMatchScoresData('{selector}')";
        }

        //public static string GetTableData(string tableSelector)
        //{
        //    return $"(function () {{ \n" +
        //        $"const rows = document.querySelectorAll('{tableSelector} tr');\n" +
        //        $"Array.from(rows, row => {{\n" +
        //        $"const columns = row.querySelectorAll('td');\n" +
        //        $"return Array.from(columns, column => column.innerText);\n" +
        //        $"}});\n" +
        //        $"}}())";
        //}

        #region Sesions

        public static string GetSesionManyFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetSesons.js");
        }

        public static string GetSesionTableFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetSesonTable.js");
        }

        public static string GetSesonsTableFirstTabSecondApproachFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetSesonsTableFirstTabSecondApproach.js");
        }

        public static string GetSesionTableFirstTabFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetSesonsTableFirstTab.js");
        }
        public static string GetMatcherDetailsFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetMatcherDetails.js");
        }

        public static string GetSesionTableThirdTabFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/SesonsTableThirdTab.js");
        }

        public static string GetSesionTableSecondTabFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/SesonsTableSecondTab.js");
        }

        public static string GetSesionManyData(string selector)
        {
            return $"GetSesons('{selector}')";
        }    public static string GetMatcherDetailsData()
        {
            return $"GetMatcherDetails()";
        }

        public static string GetSesionTableData(string selector)
        {
            return $"GetSesonsTable('{selector}')";
        }

        public static string IsItFirstAproach(string selector)
        {
            return $"IsItFirstAproach('{selector}')";
        }

        public static string GetSesionTableDataSecondAproach(string selector)
        {
            return $"GetSesonsTableV2('{selector}')";
        }

        public static string GetSesonsTableFirstTab(string selector)
        {
            return $"GetSesonsTableFirstTab('{selector}')";
        }

        public static string GetSesonsTableFirstTabSecondApproach()
        {
            return $"GetSesonsTableFirstTabSecondApproach('#main-col > div.maincontent > div.row > div.col-sm-9.col-lg-10 > table > tbody tr')";
        }

        public static string SesonsTableSecondTab()
        {
            return $"SesonsTableSecondTab('#main-col > div.maincontent > div > div.col-sm-9.col-lg-10 > div > div > table.table.table-condensed.table-striped tr')";
        }

        public static string SesonsTableThirdTab()
        {
            return $"SesonsTableThirdTab('#main-col > div.maincontent > div > div.col-sm-9.col-lg-10 > div > div > table.table.table-condensed.table-striped tr')";
        }

        #endregion Sesions

        #region Transitions

        public static string GetTransitionsFunction()
        {
            return System.IO.File.ReadAllText($"Scripts/GetTransitions.js");
        }

        public static string GetTransitions()
        {
            return $"GetTransitions('#main-col > div.maincontent > form > table > tbody > tr')";
        }

        #endregion Transitions

        public static string GetRanksData()
        {
            return "GetRanks('#main-col > div.maincontent > table.table.table-condensed.table-hover.table-striped > tbody tr')";
        }  
        public static string GetRankDetailsData()
        {
            return "GetRankDetails('#multipurpose > table > tbody tr')";
        } 
        public static string GetRankPointsDetailsData()
        {
            return "GetRankPointsDetails('#multipurpose > table > tbody tr')";
        }
    }
}
