using Polly.CircuitBreaker;
using Polly;
using Services.Interfaces;
using Shared.Models;
using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;
using PuppeteerSharp;
using Shared.Helpers;
using System.Linq;
using System.Reflection;
using System.Diagnostics;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;

namespace Services
{
    public class LiveCenterService : ILiveCenterService
    {
        public AsyncCircuitBreakerPolicy policyRetry3Times = Policy.Handle<Exception>().CircuitBreakerAsync(3, TimeSpan.FromMilliseconds(1000),
          onBreak: (exception, time) => Console.WriteLine(exception.Message),
          onReset: () => Console.WriteLine("Reset")
          );
        private readonly IBrowserService _browserService;
        private readonly IConfiguration _config;
        private readonly ISendToApiService _sendToApiService;
        private readonly ILogger<LiveCenterService> _logger;
        public LiveCenterService(IBrowserService browserService, IConfiguration config, ISendToApiService sendToApiService, ILogger<LiveCenterService> logger)
        {
            _browserService = browserService;
            _config = config;
            _sendToApiService = sendToApiService;
            _logger = logger;
        }
        public async Task GetLiveCenter(int take = 0, int skip = 0)
        {
            //var val=  _config.GetSection("ScrapeLiveCenterMatchDetails").Value;

            var browserAndPage = await _browserService.StartBrowser();
            var page = browserAndPage.page;
            try
            {
                await page.ClickAsync(Constants.LiveCenterSelector);
                await page.WaitForSelectorAsync("#filter1_id");
                await page.WaitForSelectorAsync("#filter4_id");

                var jsScriptDivison = JavascriptHelpers.GetAllValuesFromDropDown("filter4_id");

                var divisionResults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(jsScriptDivison));

                //EXCLUDE ALLA (all)
                divisionResults = divisionResults.Where(a => a.Value != "").ToArray();

                Console.WriteLine($"ALL => {String.Join("----", divisionResults.Select(a => a.Text))}\n\n");
                Console.WriteLine("");
                Console.WriteLine("------------------------------------------------------------------------");
                Console.WriteLine("");

                if (take != 0 || skip != 0)
                {
                    divisionResults = divisionResults.Skip(skip).Take(take).ToArray();

                    Console.WriteLine($"FILTERED => {String.Join("----", divisionResults.Select(a => a.Text))}");
                }

                var injectDevisionQueries = JavascriptHelpers.DivisionDataFunction();
                await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(injectDevisionQueries));

                foreach (var division in divisionResults)
                {
                    await policyRetry3Times.ExecuteAsync(async () => await page.SelectAsync("#filter4_id", division.Value));
                    await Task.Delay(300);
                    await policyRetry3Times.ExecuteAsync(async () => await GetTeamDivisionDataByPeriod(page, division.Text));
                }
                await browserAndPage.browser.CloseAsync();
            }
            catch (Exception e)
            {
                _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetLiveCenter), e.Message, e.StackTrace);
                await browserAndPage.browser.CloseAsync();

                //await GetLiveCenter(take, skip);

            }

        }

        public async Task StartProcesses(int numberOfProcesses)
        {
            var assemblyInfo = Assembly.GetCallingAssembly().GetName();
            var direcotry = assemblyInfo.CodeBase.Replace("file:///", "");
            var lastIndex = direcotry.LastIndexOf("/");
            direcotry = direcotry.Substring(0, lastIndex);

            var browserAndPage = await _browserService.StartBrowser(true);
            var page = browserAndPage.page;

            await page.ClickAsync(Constants.LiveCenterSelector);
            await page.WaitForSelectorAsync("#filter1_id");
            await page.WaitForSelectorAsync("#filter4_id");

            var jsscriptdivison = JavascriptHelpers.GetAllValuesFromDropDown("filter4_id");

            var divisionresults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(jsscriptdivison));
            divisionresults = divisionresults.Where(a => a.Value != "").ToArray();

            await browserAndPage.browser.CloseAsync();

            var divisionsPerConsole = divisionresults.Length / numberOfProcesses;
            var filename = "WebScraper.exe";
            if (Environment.OSVersion.Platform == PlatformID.Unix)
            {
                Console.WriteLine($"{Environment.OSVersion.Platform.ToString()}");
                filename = "WebScraper";
            }
            var skip = 0;
            for (int i = 0; i < numberOfProcesses; i++)
            {
                try
                {
                    ProcessStartInfo startInfo = new ProcessStartInfo
                    {

                        FileName = filename,
                        WindowStyle = ProcessWindowStyle.Normal,
                        Arguments = $"6 {divisionsPerConsole} {skip}",
                        UseShellExecute = true,
                    };

                    Process proc = new Process();
                    proc.StartInfo = startInfo;
                    proc.Start();
                    skip += divisionsPerConsole;
                }
                catch (Exception e)
                {
                  
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(StartProcesses), e.Message, e.StackTrace);

                }
            }
            Environment.Exit(0);
        }
        private async Task GetTeamDivisionDataByPeriod(Page page, string divisionName)
        {
            var jsScriptPeriod = JavascriptHelpers.GetAllValuesFromDropDown("filter1_id");
            var periodResults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(jsScriptPeriod));
            //foreach (var period in periodResults)
            for (int i = 0; i < periodResults.Count(); i++)
            {

                await policyRetry3Times.ExecuteAsync(async () => await page.SelectAsync("#filter1_id", periodResults[i].Value));
                await Task.Delay(300);
                //var teamDetails = await GetTeamDetails(page);
                var teamDetails = new List<TeamDetails>();
                try
                {
                    teamDetails = await GetTeamDetails(page);
                    var data = new LiveCenterV2() { Devision = divisionName, Period = periodResults[i].Text, Details = teamDetails };

                    var sendToHostUrlUrl = _config.GetSection("LiveCenterUrl").GetValue<string>("Host");
                    var sendToActionUrl = _config.GetSection("LiveCenterUrl").GetValue<string>("Action");
                    await _sendToApiService.SendtoApi<LiveCenterV2>(data, sendToHostUrlUrl, sendToActionUrl);
                    Console.WriteLine($"Devision : {data.Devision} | Period : {data.Period}");
                }
                //catch (PuppeteerSharp.SelectorException e)
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
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetTeamDivisionDataByPeriod), e.Message, e.StackTrace);

                }


            }
        }

        private async Task<List<TeamDetails>> GetTeamDetails(Page page)
        {
            var tableDataQuery = JavascriptHelpers.GetDivisionTableData("#matches > div > table tr");
            var tableResults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<Team>>(tableDataQuery));
            tableResults = tableResults.Where(a => a.Id != "").ToList();

            //foreach (var team in tableResults)
            for (int i = 0; i < tableResults.Count; i++)
            {

                await page.ClickAsync($"#{tableResults[i].Id}");
                await page.WaitForSelectorAsync("#matchtable");
                await Task.Delay(300);
                try
                {
                    //var playerScore = await GetPlayerMathcScore(page);
                    var playerScore = await policyRetry3Times.ExecuteAsync(async () => await GetPlayerMathcScore(page));
                    tableResults[i].TeamDetails.Mathces = playerScore;
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
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetTeamDetails), e.Message, e.StackTrace);

                }

                //tableResults[i].TeamDetails.Mathces = mathResult;
            }
            var teamDetails = tableResults.Select(a => a.TeamDetails).ToList();
            return teamDetails;
        }

        private async Task<List<Mathc>> GetPlayerMathcScore(Page page)
        {
            var mathQuery = JavascriptHelpers.GetMathcTableData("#divMatches > table > tbody > tr > td:nth-child(2) > table tr");
            var mathesResult = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<Mathc>>(mathQuery));
            mathesResult = mathesResult.Where(a => a.Id != "").ToList();

            //foreach (var match in mathesResult)
            for (int i = 0; i < mathesResult.Count; i++)
            {

                await policyRetry3Times.ExecuteAsync(async () => await page.ClickAsync($"#{mathesResult[i].Id.Replace(";", "\\;")}"));
                await page.WaitForSelectorAsync("#matchdet_main > table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(2)");
                await Task.Delay(300);
                try
                {
                    var matchDetails = await GetMatchDetails(page);
                    mathesResult[i].GameSets = matchDetails;
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
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetPlayerMathcScore), e.Message, e.StackTrace);
                }

            }
            return mathesResult;
        }

        private async Task<List<GameSets>> GetMatchDetails(Page page)
        {
            var scrapeDetails = _config.GetValue<bool>("ScrapeLiveCenterMatchDetails");
            var mathcDetailsQuery = JavascriptHelpers.GetMathcDetailsTableData("#matchdet_main > table > tbody > tr:nth-child(2) > td > table > tbody > tr:nth-child(2)");
            var mathDetailsResult = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<GameSets>>(mathcDetailsQuery));

            var haveDetails = await page.EvaluateExpressionAsync<bool>("document.querySelector('#tbl_display_match > tbody').children[2].children[0].children[0].childElementCount > 0");
            if (scrapeDetails)
            {
                if (haveDetails)
                {
                    foreach (var details in mathDetailsResult)
                    {
                        await policyRetry3Times.ExecuteAsync(async () => await page.ClickAsync($"#{details.Id.Replace(";", "\\;")}"));
                        await Task.Delay(300);
                        var mathcScoreQuery = JavascriptHelpers.GetMathcScoresData("#tbl_display_match > tbody > tr:nth-child(3) > td > table tr");
                        var mathcScoreResult = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<SetDetails>>(mathcScoreQuery));
                        details.SetDetails = mathcScoreResult;
                    }
                }
            }



            return mathDetailsResult;
        }
    }
}
