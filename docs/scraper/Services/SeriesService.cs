using Polly.CircuitBreaker;
using Polly;
using Services.Interfaces;
using System;
using System.Collections.Generic;
using System.Text;
using PuppeteerSharp;
using Shared.Models;
using System.Linq;
using System.Threading.Tasks;
using Shared.Helpers;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Shared.Enums;

namespace Services
{
    public class SeriesService : ISeriesService
    {
        public AsyncCircuitBreakerPolicy policyRetry3Times = Policy.Handle<Exception>().CircuitBreakerAsync(3, TimeSpan.FromMilliseconds(1000),
      onBreak: (exception, time) => Console.WriteLine(exception.Message),
      onReset: () => Console.WriteLine("Reset")
      );
        private readonly IBrowserService _browserService;
        private readonly ISendToApiService _sendToApiService;
        private readonly IConfiguration _config;
        private readonly ILogger<SeriesService> _logger;
        public SeriesService(IBrowserService browserService, ISendToApiService sendToApiService, IConfiguration config, ILogger<SeriesService> logger)
        {
            _browserService = browserService;
            _sendToApiService = sendToApiService;
            _config = config;
            _logger = logger;
        }


        public async Task GetSeries(int period = 0, DropDownDirection? destination = null)
        {

            var browserAndPage = await _browserService.StartBrowser();
            var page = browserAndPage.page;
            try
            {
                await page.WaitForSelectorAsync(Constants.SeriesSelector);
                await page.ClickAsync(Constants.SeriesSelector);
                await page.WaitForSelectorAsync("#main-col > div.maincontent > div > div");
                await Task.Delay(new TimeSpan(0, 0, 1));
                var sesionQuery = JavascriptHelpers.GetSesionManyFunction();

                await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(sesionQuery));
                var sesionData = JavascriptHelpers.GetSesionManyData("#main-col > div.maincontent > div > div > div > div > ul li");
                var sesions = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<Seasons>>(sesionData));
                await GetSeasonsData(page, sesions, period, destination, browserAndPage.browser);
                await browserAndPage.browser.CloseAsync();
            }
            catch (Exception e)
            {
                _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetSeries), e.Message, e.StackTrace);
                await browserAndPage.browser.CloseAsync();
                //await GetSeries(period, destination);
            }

        }

        private async Task GetSeasonsData(Page page, List<Seasons> seasons, int period, DropDownDirection? destination, Browser browser)
        {
            int counter = 0;
            var topLvl = seasons[0];
            seasons.Remove(topLvl);

            var setSectionsQuery = JavascriptHelpers.GetSesionManyData($"testtesttest");

            foreach (var topLvlSeason in topLvl.SesionParts)
            {
                bool process = true;
                if (period > 0)
                {


                    var values = topLvlSeason.Label.Split(" ")[1].Split("/");
                    if (values.Length == 2)
                    {
                        var firstDate = int.Parse(values[0]);
                        var secondDate = int.Parse(values[1]);

                        if (destination.HasValue)
                        {
                            if (destination.Value == DropDownDirection.GreaterOrEqualTo)
                            {
                                if (period <= firstDate || period <= secondDate)
                                {
                                    process = true;
                                }
                                else
                                {
                                    process = false;
                                }
                            }
                            else
                            {
                                if (period >= firstDate || period >= secondDate)
                                {
                                    process = true;
                                }
                                else
                                {
                                    process = false;
                                }
                            }
                        }


                    }
                }
                if (process)
                {
                    await page.ClickAsync($"a[href='{topLvlSeason.Link.Replace("https://www.profixio.com/fx/", "")}']");
                    //await Task.Delay(new TimeSpan(0, 0, 1));
                    await Task.Delay(300);
                    var sesionQuery = JavascriptHelpers.GetSesionManyFunction();
                    await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(sesionQuery));

                    if (counter != 0 || period > 0)
                    {
                        var sesionData = JavascriptHelpers.GetSesionManyData($"#main-col > div.maincontent > div > div > div > div > ul li");
                        seasons = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<Seasons>>(sesionData));
                        seasons.Remove(seasons[0]);
                    }
                    counter++;

                    await page.EvaluateExpressionAsync(setSectionsQuery);
                    await Task.Delay(50);
                    await GetSeriesData(page, seasons, topLvlSeason.Label, browser);
                }

            }
        }

        private async Task GetSeriesData(Page page, List<Seasons> seasons, string period, Browser browser)
        {
            var sendToHostUrlUrl = _config.GetSection("SeriesUrl").GetValue<string>("Host");
            var sendToActionUrl = _config.GetSection("SeriesUrl").GetValue<string>("Action");

            for (int sesionIndex = 0; sesionIndex < seasons.Count; sesionIndex++)
            {
                try
                {
                    var series = new SeriesDto() { SeriesName = seasons[sesionIndex].Title, Period = period };
                    var details = await GetSeriesDetails(page, seasons[sesionIndex], period, browser);
                    series.Session = details;

                    await _sendToApiService.SendtoApi<SeriesDto>(series, sendToHostUrlUrl, sendToActionUrl);
                }
                catch (Exception e)
                {

                    sesionIndex--;
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetSeriesData), e.Message, e.StackTrace);
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

        private async Task<List<Session>> GetSeriesDetails(Page page, Seasons seasons, string periodName, Browser browser)
        {
            //var setSectionsQuery = GetSesionManyData($"testtesttest");
            //var sesionQuery = GetSesionManyFunction();
            var listOfSesins = new List<Session>();

            for (int sesionPartIndex = 0; sesionPartIndex < seasons.SesionParts.Count; sesionPartIndex++)
            {
                try
                {
                    if (seasons.SesionParts[sesionPartIndex].SessionPartNested != null)
                    {
                        listOfSesins.AddRange(await NestedLinks(page, seasons.SesionParts[sesionPartIndex], seasons.Title, periodName, browser));
                    }
                    else if (seasons.SesionParts[sesionPartIndex].Link != "")
                    {
                        listOfSesins.Add(await BaseLinksData(page, seasons.SesionParts[sesionPartIndex], periodName, browser));
                    }
                }
                catch (Exception e)
                {
                    sesionPartIndex--;
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetSeriesDetails), e.Message, e.StackTrace);
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
            return listOfSesins;
        }

        private async Task<Session> BaseLinksData(Page page, SessionPart seasonsPart, string periodName, Browser browser)
        {
            var setSectionsQuery = JavascriptHelpers.GetSesionManyData($"testtesttest");
            var sesionQuery = JavascriptHelpers.GetSesionManyFunction();
            await page.ClickAsync($"a[href='{seasonsPart.Link.Replace("https://www.profixio.com/fx/", "")}']");

            await Task.Delay(new TimeSpan(0, 0, 1));
            await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(sesionQuery));
            await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(setSectionsQuery));
            var data = await GetSesionData(page, seasonsPart.Label, periodName, browser);
            await Task.Delay(50);
            return data;
        }

        private async Task<List<Session>> NestedLinks(Page page, SessionPart seasonsPart, string sessionTitle, string periodName, Browser browser)
        {
            var listOfSesins = new List<Session>();
            var setSectionsQuery = JavascriptHelpers.GetSesionManyData($"testtesttest");
            var sesionQuery = JavascriptHelpers.GetSesionManyFunction();
            //foreach (var item in seasonsPart.SessionPartNested)
            for (int i = 0; i < seasonsPart.SessionPartNested.Count; i++)
            {
                try
                {
                    await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(sesionQuery));
                    await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(setSectionsQuery));

                    await page.ClickAsync($"a[selector='{seasonsPart.Label}{sessionTitle}']");
                    await Task.Delay(200);

                    var link = $"a[href='{seasonsPart.SessionPartNested[i].Link.Replace("https://www.profixio.com/fx/", "")}']";

                    var nodeExsists = await page.QuerySelectorAsync(link);

                    if (nodeExsists != null)
                    {
                        await page.ClickAsync(link);
                        //await Task.Delay(new TimeSpan(0, 0, 1));
                        await Task.Delay(500);
                        await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(sesionQuery));
                        await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(setSectionsQuery));
                        var data = await GetSesionData(page, seasonsPart.SessionPartNested[i].Label, periodName, browser);
                        listOfSesins.Add(data);
                        await Task.Delay(50);
                    }
                    else
                    {
                        _logger.LogWarning($"Node Not Exsists --> {link}");
                    }


                }
                catch (Exception e)
                {
                    i--;
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetSeriesDetails), e.Message, e.StackTrace);

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
            return listOfSesins;
        }

        private async Task<Session> GetSesionData(Page page, string Name, string periodName, Browser browser)
        {
            var topTableData = await GetTopTableData(page);
            var firstTabData = await GetGetSesonsTableFirstTab(page, browser);
            var secondQueryData = await GetSesonsTableSecondTab(page);
            var thirdQueryData = await GetSesonsTableThirdTab(page);

            var result = new Session
            {
                SesionTableDataFirstAproach = topTableData.first,
                SesionTableDataSecondAproach = topTableData.second,
                MatcherFirstAproach = firstTabData.furstAproach,
                MatcherSecondAproach = firstTabData.secondAproach,
                MatchStatisticPerPlayer = thirdQueryData,
                MatchStatisticPerTeam = secondQueryData,
                Name = Name
            };
            Console.WriteLine($"Devision : {Name} Period : {periodName}");
            return result;
        }

        private async Task<List<SesionMatchStatisticPerPlayer>> GetSesonsTableThirdTab(Page page)
        {
            var thirdQueryData = new List<SesionMatchStatisticPerPlayer>();
            var nodeExsists = await page.QuerySelectorAsync("[role=\"tablist\"]");
            if (nodeExsists == null) { return (thirdQueryData); }
            await page.ClickAsync("[role=\"tablist\"] li:nth-child(3) a");
            //await Task.Delay(new TimeSpan(0, 0, 1));
            await Task.Delay(500);
            var thirdTabqueryInit = JavascriptHelpers.GetSesionTableThirdTabFunction();
            await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(thirdTabqueryInit));
            var thirdTabQuery = JavascriptHelpers.SesonsTableThirdTab();
            thirdQueryData = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<SesionMatchStatisticPerPlayer>>(thirdTabQuery));
            return thirdQueryData;
        }

        private async Task<List<SesionTableSecondTab>> GetSesonsTableSecondTab(Page page)
        {
            var secondQueryData = new List<SesionTableSecondTab>();
            var nodeExsists = await page.QuerySelectorAsync("[role=\"tablist\"]");
            if (nodeExsists == null) { return (secondQueryData); }
            await page.ClickAsync("[role=\"tablist\"] li:nth-child(2) a");
            //await Task.Delay(new TimeSpan(0, 0, 1));
            await Task.Delay(500);
            //await page.WaitForSelectorAsync("#main-col > div.maincontent > div > div.col-sm-9.col-lg-10 > div > div > table.table.table-condensed.table-striped tr");
            try
            {
                var secondTabQueryInit = JavascriptHelpers.GetSesionTableSecondTabFunction();
                await page.EvaluateExpressionAsync(secondTabQueryInit);
                var secondTabQuery = JavascriptHelpers.SesonsTableSecondTab();
                secondQueryData = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<SesionTableSecondTab>>(secondTabQuery));
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
            }

            return secondQueryData;
        }

        private async Task<(List<SesionTableFirstTab> furstAproach, List<SesionTableFirstTabSecondAproach> secondAproach)> GetGetSesonsTableFirstTab(Page page, Browser browser)
        {
            var firstTabSecondAproachData = new List<SesionTableFirstTabSecondAproach>();
            var firstTabData = new List<SesionTableFirstTab>();

            var nodeExsists = await page.QuerySelectorAsync("[role=\"tablist\"]");
            if (nodeExsists == null) { return (firstTabData, firstTabSecondAproachData); }
            await policyRetry3Times.ExecuteAsync(async () => await page.ClickAsync("[role=\"tablist\"] li:nth-child(1) a"));
            //await Task.Delay(new TimeSpan(0, 0, 1));
            await Task.Delay(500);
            await page.WaitForSelectorAsync("#main-col > div.maincontent > div.row > div.col-sm-9.col-lg-10 > table > tbody tr");
            var initFirstTab = JavascriptHelpers.GetSesionTableFirstTabFunction();
            var initFirstTabSecondAproach = JavascriptHelpers.GetSesonsTableFirstTabSecondApproachFunction();
            await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(initFirstTab));
            await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(initFirstTabSecondAproach));
            try
            {
                var firstTabQuery = JavascriptHelpers.GetSesonsTableFirstTab("#main-col > div.maincontent > div.row > div.col-sm-9.col-lg-10 > table > tbody tr");
                firstTabData = await page.EvaluateExpressionAsync<List<SesionTableFirstTab>>(firstTabQuery);
                await GetDetails(firstTabData, browser);
                if (firstTabData.Any(a => a.Details.Count() == 0))
                {
                    firstTabData = new List<SesionTableFirstTab>();
                    var firstTabSecondAproachQuery = JavascriptHelpers.GetSesonsTableFirstTabSecondApproach();
                    firstTabSecondAproachData = await page.EvaluateExpressionAsync<List<SesionTableFirstTabSecondAproach>>(firstTabSecondAproachQuery);
                    await GetDetails(firstTabSecondAproachData, browser);
                }
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
                var firstTabQuery = JavascriptHelpers.GetSesonsTableFirstTabSecondApproach();
                firstTabSecondAproachData = await page.EvaluateExpressionAsync<List<SesionTableFirstTabSecondAproach>>(firstTabQuery);
                await GetDetails(firstTabSecondAproachData, browser);
            }
            return (firstTabData, firstTabSecondAproachData);
        }
        private async Task GetDetails(List<SesionTableFirstTab> data, Browser browser)
        {
            var page = await browser.NewPageAsync();
            try
            {
                for (int i = 0; i < data.Count; i++)
                {
                    for (int j = 0; j < data[i].Details.Count; j++)
                    {
                        if (data[i].Details[j].Link != "" && data[i].Details[j].Link != null)
                        {
                            await page.GoToAsync(data[i].Details[j].Link);
                            await page.WaitForSelectorAsync("#div_kamplive");
                            await page.EvaluateExpressionAsync(JavascriptHelpers.GetMatcherDetailsFunction());
                            var result = await page.EvaluateExpressionAsync<List<SesionFirtTabDetails>>(JavascriptHelpers.GetMatcherDetailsData());
                            var date = await page.EvaluateExpressionAsync<string>(@"document.querySelector('table >tbody>tr>:nth-child(2)').innerText");
                            data[i].Details[j].DateFromDetails = date;
                            data[i].Details[j].Details = result;

                        }
                    }
                }
                await page.CloseAsync();
            }
            catch (Exception)
            {
                await page.CloseAsync();
                throw;
            }

            await page.CloseAsync();
        }
        private async Task GetDetails(List<SesionTableFirstTabSecondAproach> data, Browser browser)
        {
            var page = await browser.NewPageAsync();

            for (int i = 0; i < data.Count; i++)
            {
                for (int j = 0; j < data[i].Details.Count; j++)
                {
                    if (data[i].Details[j].Link != "" && data[i].Details[j].Link != null)
                    {
                        try
                        {
                            await page.GoToAsync(data[i].Details[j].Link);
                            await page.WaitForSelectorAsync("#div_kamplive");
                            await page.EvaluateExpressionAsync(JavascriptHelpers.GetMatcherDetailsFunction());
                            var result = await page.EvaluateExpressionAsync<List<SesionFirtTabDetails>>(JavascriptHelpers.GetMatcherDetailsData());
                            var date = await page.EvaluateExpressionAsync<string>(@"document.querySelector('table >tbody>tr>:nth-child(2)').innerText");
                            data[i].Details[j].DateFromDetails = date;
                            data[i].Details[j].Details = result;
                        }
                        catch (Exception)
                        {
                        }


                    }
                }
            }
            await page.CloseAsync();
        }
        private async Task<(List<SesionTableData> first, List<SesionTableDataSecondAproach> second)> GetTopTableData(Page page)
        {
            var initTableDataQuery = JavascriptHelpers.GetSesionTableFunction();

            await page.EvaluateExpressionAsync(initTableDataQuery);
            var tableDataQuery = JavascriptHelpers.GetSesionTableData("#tabell_std tr");
            var checkAproach = JavascriptHelpers.IsItFirstAproach("#tabell_std tr");

            var IsFirstAproach = await page.EvaluateExpressionAsync<bool?>(checkAproach);
            var tableDataFirstAproach = new List<SesionTableData>();
            var tableDataSecondAproach = new List<SesionTableDataSecondAproach>();

            if (IsFirstAproach.HasValue)
            {
                if (IsFirstAproach.Value)
                {
                    tableDataFirstAproach = await page.EvaluateExpressionAsync<List<SesionTableData>>(tableDataQuery);
                }
                else
                {
                    tableDataQuery = JavascriptHelpers.GetSesionTableDataSecondAproach("#tabell_std tr");
                    tableDataSecondAproach = await page.EvaluateExpressionAsync<List<SesionTableDataSecondAproach>>(tableDataQuery);
                }
            }
            else
            {
                Console.WriteLine("NO DATA");
            }



            return (tableDataFirstAproach, tableDataSecondAproach);
        }
    }
}
