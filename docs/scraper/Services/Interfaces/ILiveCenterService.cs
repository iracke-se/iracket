using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;

namespace Services.Interfaces
{
    public interface ILiveCenterService
    {
        Task GetLiveCenter(int take = 0, int skip = 0);
        Task StartProcesses(int numberOfProcesses);
    }
}
