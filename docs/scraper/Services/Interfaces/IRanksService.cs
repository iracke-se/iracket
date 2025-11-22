using PuppeteerSharp;
using Shared.Enums;
using Shared.Models;
using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;

namespace Services.Interfaces
{
    public interface IRanksService
    {
        Task<DropDownTextAndValue[]> GetPeriods(Gender gender, Page page = null);
        Task GetRanking(Gender gender, DateTime date, DropDownDirection direction);
    }
}
