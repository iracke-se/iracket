using Microsoft.Extensions.Configuration;
using PuppeteerSharp;
using Services.Interfaces;
using Shared.Models;
using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;

namespace Services
{
    public class BrowserService : IBrowserService
    {

        private readonly IConfiguration _configuration;
        public BrowserService(IConfiguration configuration)
        {
            _configuration = configuration;
        }

        public async Task<(Page page, Browser browser)> StartBrowser(bool headles = false)
        {
            //var Args = new[] { "--no-sandbox", "--disable-gpu-rasterization", "--disable-remote-extensions" };

            if (headles == false) headles = _configuration.GetValue<bool>("Headles");

            var options = new LaunchOptions
            {
                Headless = headles,
                DefaultViewport = null,
                IgnoreHTTPSErrors = true,
                Devtools = false,
                Args = new string[] {
                            "--disable-dev-shm-usage",
                            "--disable-accelerated-2d-canvas",
                            "--disable-gpu"},
                IgnoredDefaultArgs = new string[] { "--enable-automation" }
            };

            //Console.WriteLine("Downloading chromium");
            //await new BrowserFetcher().DownloadAsync(BrowserFetcher.DefaultRevision);
            await new BrowserFetcher().DownloadAsync();
            var browser = await Puppeteer.LaunchAsync(options);
            //var page = await browser.NewPageAsync();
            var page = await browser.NewPageAsync();
           

            await page.GoToAsync("https://www.profixio.com/fx/lisens/public_oversikt.php");
            await page.EvaluateFunctionOnNewDocumentAsync(@"function() { Object.defineProperty(navigator, 'webdriver', { get: () => undefined});}");
            if (page.Url.Contains("login"))
            {
                await page.WaitForSelectorAsync(Constants.LoginPageSelector);
                await page.ClickAsync(Constants.LoginPageSelector);
                await page.WaitForSelectorAsync("#hoved-meny > li:nth-child(5) > a");
            }
            return (page, browser);
        }
    }
}
