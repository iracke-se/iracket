using Shared.Enums;
using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;

namespace Services.Interfaces
{
    public interface ISeriesService
    {
        Task GetSeries(int period = 0, DropDownDirection? destination = null);
    }
}
