using Polly.CircuitBreaker;
using Polly;
using PuppeteerSharp;
using Services.Interfaces;
using Shared.Enums;
using Shared.Helpers;
using Shared.Models;
using System;
using System.Collections.Generic;
using System.Globalization;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using System.Net.Http;
using System.Net.Http.Headers;
using HtmlAgilityPack;
using RestSharp;

namespace Services
{
    public class RanksService : IRanksService
    {
        public AsyncCircuitBreakerPolicy policyRetry3Times = Policy.Handle<Exception>().CircuitBreakerAsync(3, TimeSpan.FromMilliseconds(10000),
        onBreak: (exception, time) => Console.WriteLine(exception.Message),
        onReset: () => Console.WriteLine("Reset")
        );
        private readonly IBrowserService _browserService;
        private readonly IConfiguration _config;
        private readonly ISendToApiService _sendToApiService;
        private readonly ILogger<RanksService> _logger;

        public RanksService(IBrowserService browserService, IConfiguration config, ISendToApiService sendToApiService, ILogger<RanksService> logger)
        {
            _browserService = browserService;
            _config = config;
            _sendToApiService = sendToApiService;
            _logger = logger;
        }


        public async Task GetRanking(Gender gender, DateTime date, DropDownDirection direction)
        {

            var browserAndPage = await _browserService.StartBrowser();
            var page = browserAndPage.page;
            try
            {
                var dropDownData = await GetPeriods(gender, page);

                var filterdData = new List<DropDownTextAndValue>();
                if (date != default)
                {
                    for (int i = 0; i < dropDownData.Length; i++)
                    {
                        var parsed = DateTime.ParseExact(dropDownData[i].Text, "yyyy.MM.dd", CultureInfo.InvariantCulture);

                        if (direction == DropDownDirection.GreaterOrEqualTo)
                        {
                            if (parsed.Date >= date.Date)
                            {
                                filterdData.Add(dropDownData[i]);
                            }
                        }
                        else if (direction == DropDownDirection.LessOrEqualTo)
                        {
                            if (parsed.Date <= date.Date)
                            {
                                filterdData.Add(dropDownData[i]);
                            }
                        }
                    }
                    dropDownData = filterdData.ToArray();
                }

                await GetRankingData(page, dropDownData, gender);
                await browserAndPage.browser.CloseAsync();
            }
            catch (Exception e)
            {
                _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetRanking), e.Message, e.StackTrace);

                await browserAndPage.browser.CloseAsync();
                //await GetRanking(gender, date, direction);
            }

        }

        public async Task<DropDownTextAndValue[]> GetPeriods(Gender gender, Page page = null)
        {
            if (page == null)
            {
                var browserAndPage = await _browserService.StartBrowser();
                page = browserAndPage.page;
            }

            await page.ClickAsync(Constants.RankingsSelector);
            await Task.Delay(500);
            await page.ClickAsync("#main-col > div.meny > div > div.undermeny > ul > li:nth-child(2) > a");
            await page.WaitForSelectorAsync("#main-col > div.maincontent > table > tbody > tr > td:nth-child(2) > a");
            await page.ClickAsync($"#main-col > div.maincontent > table > tbody > tr > td:nth-child({(int)gender}) > a");
            await page.WaitForSelectorAsync("#searchform > select:nth-child(2)");
            var dropdownQuery = JavascriptHelpers.GetAllValuesFromDropDownRanksPeriod();
            var dropDownData = await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(dropdownQuery);
            return dropDownData;
        }

        private async Task GetRankingData(Page page, DropDownTextAndValue[] periods, Gender gender)
        {
            //foreach (var period in periods)
            for (int i = 0; i < periods.Length; i++)
            {
                try
                {
                    await page.SelectAsync("[name='rid']", periods[i].Value);
                    await Task.Delay(500);

                    var devisionInitQuery = JavascriptHelpers.GetAllValuesFromDropDownRanksDevisions();
                    await GetRanksForDevisions(page, devisionInitQuery, periods[i].Text, gender);
                }
                catch (Exception e)
                {
                    if (e is TargetClosedException)
                    {
                        Environment.Exit(1);
                    }
                    if (e.Message.Contains("Session closed"))
                    {
                        Environment.Exit(1);
                    }

                    i--;
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetRankingData), e.Message, e.StackTrace);
                }

            }
        }

        private async Task GetRanksForDevisions(Page page, string devisionInitQuery, string period, Gender gender)
        {
            var devisinDropDownData = await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(devisionInitQuery);
            devisinDropDownData = devisinDropDownData.Where(a => a.Value != "").ToArray();
            var data = new RankDto() { Period = period, Gender = gender };
            //foreach (var devision in devisinDropDownData)
            for (int i = 0; i < devisinDropDownData.Length; i++)
            {
                try
                {
                    var rankInit = JavascriptHelpers.GetRanksFunction();
                    await page.SelectAsync("[name='distr']", devisinDropDownData[i].Value);
                    await Task.Delay(100);
                    await page.WaitForSelectorAsync("#searchform > input.btn.btn-default");
                    await page.ClickAsync("#searchform > input.btn.btn-default");

                    await page.WaitForSelectorAsync("#main-col > div.maincontent > table.table.table-condensed.table-hover.table-striped");
                    await Task.Delay(1000);
                    await page.EvaluateExpressionAsync(rankInit);
                    var rankDataQuery = JavascriptHelpers.GetRanksData();
                    //var rankData = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<RankResuls>>(rankDataQuery));
                    var rankData = new List<RankResuls>();
                    int numberOfErrors = 0;
                    while (true)
                    {
                        try
                        {
                            rankData = await page.EvaluateExpressionAsync<List<RankResuls>>(rankDataQuery);
                            //TODO Uncomment This
                            await GetDetails(page, rankData);
                            break;
                        }
                        catch (Exception e)
                        {
                            if (e is TargetClosedException)
                            {
                                Environment.Exit(1);
                            }
                            if (e.Message.Contains("Session closed"))
                            {
                                Environment.Exit(1);
                            }
                            if (numberOfErrors > 10)
                            {
                                Environment.Exit(1);
                            }
                            numberOfErrors++;
                        }
                    }

                    await page.EvaluateExpressionAsync("document.querySelector('#main-col > div.maincontent > table.table.table-condensed.table-hover.table-striped').remove()");
                    data.Devision = devisinDropDownData[i].Text;
                    data.Data = rankData;

                    var sendToHostUrlUrl = _config.GetSection("RanksUrl").GetValue<string>("Host");
                    var sendToActionUrl = _config.GetSection("RanksUrl").GetValue<string>("Action");
                    await _sendToApiService.SendtoApi<RankDto>(data, sendToHostUrlUrl, sendToActionUrl);

                    Console.WriteLine($"Period : {data.Period} Devision : {data.Devision}");

                    //await SendtoApi<RankDto>(date);
                }
                catch (Exception e)
                {
                    i--;
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetRanksForDevisions), e.Message, e.StackTrace);
                    if (e is TargetClosedException)
                    {
                        Environment.Exit(1);
                    }
                    if (e.Message.Contains("Session closed"))
                    {
                        Environment.Exit(1);
                    }
                }

            }
        }

        private async Task GetDetails(Page page, List<RankResuls> rankResuls)
        {
            try
            {
                var detailFunction = JavascriptHelpers.GetRankDetailsFunction();
                //foreach (var item in rankResuls)
                for (int i = 0; i < rankResuls.Count; i++)
                {
                    var selector = $"#{rankResuls[i].NotCleardId}".Replace(":", @"\:");
                    await page.ClickAsync(selector);
                    await page.WaitForSelectorAsync("#multipurpose");
                    await Task.Delay(200);
                    await page.EvaluateExpressionAsync(detailFunction);
                    var details = await page.EvaluateExpressionAsync<RankDetails>(JavascriptHelpers.GetRankDetailsData());
                    rankResuls[i].RankDetails = details;
                    await GetPointsDetails(page, rankResuls[i]);
                }
            }
            catch (Exception e)
            {
                _logger.LogError("{@e}",e.Message);
                Console.WriteLine(e.Message);
            }
        }



        public async Task<RankResuls> GetPointsDetails(Page page, RankResuls details)
        {
            Console.WriteLine($"Start Processed Details for {details.Name}");
            var tasks = details.RankDetails.Details.Select(async item =>
                        {
                            var response = await HttpCallForDetails(item.PointId);
                            item.PointsDetails = response;

                        });

            await Task.WhenAll(tasks);
            Console.WriteLine($"END Processed Details for {details.Name}");
            return details;
        }

        //public async Task GetPointsDetails(Page page, RankDetails details)
        //{
        //    for (int i = 0; i < details.Details.Count; i++)
        //    {
        //        var selector = $"#{details.Details[i].PointId}".Replace(":", @"\:");
        //        await page.ClickAsync(selector);




        //        await page.WaitForSelectorAsync("#multipurpose > button:nth-child(5)");
        //        //await Task.Delay(300);
        //    }
        //}


        public async Task<RankPointsDetails> HttpCallForDetails(string id)
        {
            var splited = id.Split(':');
            var paramss = new Dictionary<string, string>();

            paramss.Add("type", splited[0]);
            paramss.Add("spillerid", splited[1]);
            paramss.Add("kjoring", splited[2]);




            var restclient = new RestClient("https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_det.php");
            restclient.Timeout = -1;
            var request = new RestRequest(Method.POST);
            //request.AddHeader("Cookie", "PHPSESSID=kff3jli304381n0o144rmlbre9; _srv=p2");
            request.AlwaysMultipartFormData = true;
            request.AddParameter("type", splited[0]);
            request.AddParameter("spillerid", splited[1]);
            request.AddParameter("kjoring", splited[2]);
            IRestResponse response = await restclient.ExecuteAsync(request);
            var responseContent = response.Content;



            //using (HttpClient client = new HttpClient())
            //{
                //client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

                
                //var response = await policyRetry3Times.ExecuteAsync(async () => await client.PostAsync("https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_det.php", new FormUrlEncodedContent(paramss)));

                //HttpResponseMessage response = await client.PostAsync("https://www.profixio.com/fx/ranking_sbtf/ranking_sbtf_det.php", new FormUrlEncodedContent(paramss));
                //sw.Stop();



               



                //var responseContent = await response.Content.ReadAsStringAsync();

                var res = responseContent.Trim();
                res = res.Replace("el = document.getElementById('multipurpose');", "")
                    .Replace("el.innerHTML = ", "")
                    .Replace("el.style.visibility='visible';", "")
                    .Replace("Behaviour.apply()", "");

                res = res.Trim();
                if (res.StartsWith("\""))
                {
                    res = res.Substring(1, res.Length - 1);
                }
                if (res.EndsWith("\""))
                {
                    res = res.Substring(0, res.Length - 1);
                }

                var doc = new HtmlDocument();
                doc.LoadHtml(res);

                var table = doc.DocumentNode.SelectSingleNode("//table");
                RankPointsDetails points = new RankPointsDetails();
                if (table != null)
                {
                    var tableRows = table.SelectNodes("tr");

                    var brakeIndexes = new List<int>();
                    for (var i = 0; i < tableRows.Count; i++)
                    {
                        if (tableRows[i].ChildNodes.Count == 1)
                        {
                            brakeIndexes.Add(i);
                        }
                    }

                    if (brakeIndexes.Min() == 3)
                    {
                        points.ExPoints = tableRows[0].SelectNodes("td")[2].InnerText.Trim();
                        points.Adjustment = tableRows[1].SelectNodes("td")[2].InnerText.Trim();
                        points.ExPointsAdjusted = tableRows[2].SelectNodes("td")[2].InnerText.Trim();
                    }
                    if (brakeIndexes.Min() == 1)
                    {
                        points.ExPoints = tableRows[0].SelectNodes("td")[2].InnerText.Trim();
                    }
                    List<RankPointsMatchDetails> matches = new List<RankPointsMatchDetails>();
                    for (int i = brakeIndexes.Min() + 1; i < tableRows.Count; i++)
                    {
                        var cols = tableRows[i].SelectNodes("td");

                        if (cols != null)
                        {
                            if (cols.Count == 5)
                            {

                                if (cols[0].InnerText == "" && (cols[1].InnerText.ToLower() == "Matchpoäng".ToLower() || cols[1].InnerText.ToLower() == "MatchpoZaumlZng".ToLower()))
                                {
                                    points.MatchPoints = cols[3].InnerText;
                                }
                                else
                                {
                                    var mach = new RankPointsMatchDetails()
                                    {
                                        Status = cols[0].InnerText,
                                        Name = cols[1].InnerText,
                                        Place = cols[2].InnerText,
                                        MatchPoint = cols[3].InnerText,
                                        Date = cols[4].InnerText,
                                    };
                                    matches.Add(mach);
                                }
                            }
                            else if (cols.Count == 4 && (brakeIndexes.Max() - 1) == i)
                            {
                                points.Inactive = cols[3].InnerText;
                            }
                            else if (cols.Count == 4 && (brakeIndexes.Max() + 1) == i)
                            {
                                points.Total = cols[3].InnerText;
                            }
                        }
                    }
                    points.MathcDetails = matches;
                }
                return points;
            //}
        }
    }
}
