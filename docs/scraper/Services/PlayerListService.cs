using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;
using Polly;
using Polly.CircuitBreaker;
using PuppeteerSharp;
using Services.Interfaces;
using Shared.Helpers;
using Shared.Models;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace Services
{
    public class PlayerListService : IPlayerListService
    {
        public static AsyncCircuitBreakerPolicy policyRetry3Times = Policy.Handle<Exception>().CircuitBreakerAsync(3, TimeSpan.FromMilliseconds(1000),
           onBreak: (exception, time) => Console.WriteLine(exception.Message),
           onReset: () => Console.WriteLine("Reset")
           );

        private readonly IBrowserService _browserService;
        private readonly IConfiguration _config;
        private readonly ISendToApiService _sendToApiService;
        private readonly ILogger<PlayerListService> _logger;

        public PlayerListService(IBrowserService browserService, IConfiguration config, ISendToApiService sendToApiService, ILogger<PlayerListService> logger)
        {
            _browserService = browserService;
            _config = config;
            _sendToApiService = sendToApiService;
            _logger = logger;
        }

        public async Task GetPlayerListLicensedPlayers()
        {
            var browserAndPage = await _browserService.StartBrowser();
            var page = browserAndPage.page;
            try
            {
                await page.ClickAsync(Constants.PlayListSelector);

                var jsScriptPeriod = JavascriptHelpers.GetAllValuesFromDropDown("periode");
                var jsScriptClub = JavascriptHelpers.GetAllValuesFromDropDown("klubbid");
                await Task.Delay(500);

                var periodResults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(jsScriptPeriod));
                var clibResults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(jsScriptClub));
                clibResults = clibResults.Where(a => a.Text != "").ToArray();
                var playerListData = new List<PlayerListDto>();

                for (int i = 0; i < periodResults.Length; i++)
                {
                    try
                    {
                        var playerList = new PlayerListDto() { Period = periodResults[i].Text };

                        await page.SelectAsync("#periode", periodResults[i].Value);
                        Console.WriteLine($"PERID => {periodResults[i].Text}");

                        //await Task.Delay(new TimeSpan(0, 0, 5));

                        await GetClubResults(page, clibResults, playerList, periodResults[i].Text);
                    }
                    catch (Exception e)
                    {
                        i--;
                        Console.WriteLine($"{nameof(GetPlayerListLicensedPlayers)} -----> {e.Message}");
                    }
                }
                await browserAndPage.browser.CloseAsync();
            }
            catch (Exception e)
            {
                _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetPlayerListLicensedPlayers), e.Message, e.StackTrace);

                await browserAndPage.browser.CloseAsync();
            }

        }

        private async Task GetClubResults(Page page, DropDownTextAndValue[] clibResults, PlayerListDto playerList, string period)
        {
            //foreach (var clubElement in clibResults)
            for (int i = 0; i < clibResults.Length; i++)
            {
                try
                {
                    await page.WaitForSelectorAsync("#klubbid");
                    await Task.Delay(300);
                    var club = new Club() { ClubName = clibResults[i].Text, Period = period };
                    await policyRetry3Times.ExecuteAsync(async () => await page.SelectAsync("#klubbid", clibResults[i].Value));

                    Console.WriteLine($"CLUB -> {clibResults[i].Text}");
                    await Task.Delay(new TimeSpan(0, 0, 1));
                    var tableDataQuery = JavascriptHelpers.GetTableData(".table-condensed");
                    var tableResults = new List<List<string>>();
                    //var tableResults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<List<string>>>(tableDataQuery));
                    int numberofTImes = 0;
                    while (true)
                    {
                        try
                        {
                            tableResults = await page.EvaluateExpressionAsync<List<List<string>>>(tableDataQuery);
                            break;
                        }
                        catch (Exception e)
                        {
                            if (e is TargetClosedException)
                            {
                                Environment.Exit(1);
                            }
                            if (numberofTImes > 10)
                            {
                                Environment.Exit(1);
                            }
                            numberofTImes++;
                        }

                    }

                    var listOfPlayersForClub = new List<PlayerListTable>();

                    for (int j = 0; j < tableResults.Count; j++)
                    {
                        if (tableResults[j].Count > 0)
                        {
                            var player = new PlayerListTable
                            {
                                Surname = tableResults[j][1],
                                FirstName = tableResults[j][2],
                                Sex = tableResults[j][3],
                                DateOfBirth = tableResults[j][4],
                                LicencType = tableResults[j][5],
                                CurrentPlayerClass = tableResults[j][6]
                            };

                            listOfPlayersForClub.Add(player);
                        }
                    }
                    club.Players.AddRange(listOfPlayersForClub);

                    if (club.Players.Count > 0)
                        playerList.Club.Add(club);

                    Console.WriteLine($"PERIOD : {club.Period} | CLUB : {club.ClubName}");

                    var sendToHostUrlUrl = _config.GetSection("PlayerListPlayersUrl").GetValue<string>("Host");
                    var sendToActionUrl = _config.GetSection("PlayerListPlayersUrl").GetValue<string>("Action");

                    await _sendToApiService.SendtoApi<Club>(club, sendToHostUrlUrl, sendToActionUrl);
                    //await SendtoApi<Club>(club);

                }
                catch (Exception e)
                {
                    if (e is TargetClosedException)
                    {
                        Environment.Exit(1);
                    }

                    if(e.Message.Contains("Session closed"))
                    {
                         Environment.Exit(1);
                    }
                    i--;
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetClubResults), e.Message, e.StackTrace);

                }
            }
        }

        public async Task GetPlayerListingTransitions()
        {
            var browserAndPage = await _browserService.StartBrowser();
            var page = browserAndPage.page;

            try
            {
                await page.WaitForSelectorAsync(Constants.PlayListSelector);
                await page.ClickAsync(Constants.PlayListSelector);
                await Task.Delay(500);
                //await page.ClickAsync("#main-col > div.meny > div > div.undermeny > ul > li:nth-child(2) > a");
                await policyRetry3Times.ExecuteAsync(async () => await page.ClickAsync("#main-col > div.meny > div > div.undermeny > ul > li:nth-child(2) > a"));
                await Task.Delay(500);

                await page.WaitForSelectorAsync("#periode");
                var jsScriptPeriod = JavascriptHelpers.GetAllValuesFromDropDown("periode");
                var periodResults = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<DropDownTextAndValue[]>(jsScriptPeriod));
                periodResults = periodResults.Where(a => a.Value != "0").ToArray();
                var result = await GetTransitions(page, periodResults);
                await browserAndPage.browser.CloseAsync();
            }
            catch (Exception e)
            {

                await browserAndPage.browser.CloseAsync();
                _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetPlayerListingTransitions), e.Message, e.StackTrace);
                //await GetPlayerListingTransitions();
            }

        }

        private async Task<List<TransitionsDto>> GetTransitions(Page page, DropDownTextAndValue[] periods)
        {
            var listoForPeriods = new List<TransitionsDto>();
            //foreach (var period in periods)
            for (int i = 0; i < periods.Length; i++)
            {
                try
                {
                    var result = await GetTransitionsByPeriod(page, periods[i], i);


                    var sendToHostUrlUrl = _config.GetSection("PlayerListTransitionsUrl").GetValue<string>("Host");
                    var sendToActionUrl = _config.GetSection("PlayerListTransitionsUrl").GetValue<string>("Action");
                    await _sendToApiService.SendtoApi<TransitionsDto>(result, sendToHostUrlUrl, sendToActionUrl);

                    listoForPeriods.Add(result);
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
                    _logger.LogError("Location :{@method} Error Message :{@message} \n StackTrace : {@StackTrace}", nameof(GetTransitions), e.Message, e.StackTrace);
                }
            }
            return listoForPeriods;
        }

        private async Task<TransitionsDto> GetTransitionsByPeriod(Page page, DropDownTextAndValue period, int elementNumber)
        {
            await page.WaitForSelectorAsync("#periode");
            if (elementNumber > 0)
            {

                await page.EvaluateExpressionAsync("document.querySelector('#main-col > div.maincontent > form > table').remove()");
            }
            await policyRetry3Times.ExecuteAsync(async () => await page.SelectAsync("#periode", period.Value));
            await page.WaitForSelectorAsync("#main-col > div.maincontent > form > table > tbody > tr");
            await Task.Delay(new TimeSpan(0, 0, 1));

            var transitionQueryInit = JavascriptHelpers.GetTransitionsFunction();
            var transitionQuery = JavascriptHelpers.GetTransitions();
            await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync(transitionQueryInit));
            //var data = await page.EvaluateExpressionAsync<List<TransitionsDetailsDto>>(transitionQuery);
            //var data = await policyRetry3Times.ExecuteAsync(async () => await page.EvaluateExpressionAsync<List<TransitionsDetailsDto>>(transitionQuery));
            var data = new List<TransitionsDetailsDto>();
            int numberOfErrors = 0;
            while (true)
            {
                try
                {
                    data = await page.EvaluateExpressionAsync<List<TransitionsDetailsDto>>(transitionQuery);
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
            var result = new TransitionsDto { Period = period.Text, Data = data };
            return result;
        }
    }
}
