using PuppeteerSharp;
using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;

namespace Services.Interfaces
{
    public interface IBrowserService
    {
        Task<(Page page, Browser browser)> StartBrowser(bool headles = false);
    }
}
