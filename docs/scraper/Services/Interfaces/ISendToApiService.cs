using System;
using System.Collections.Generic;
using System.Text;
using System.Threading.Tasks;

namespace Services.Interfaces
{
    public interface ISendToApiService
    {
        Task SendtoApi<T>(T data, string url,string path);
    }
}
