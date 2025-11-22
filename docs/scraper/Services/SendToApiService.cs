using Microsoft.Extensions.Logging;
using Newtonsoft.Json;
using RestSharp;
using Services.Interfaces;
using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.IO;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Threading.Tasks;

namespace Services
{
    public class SendToApiService : ISendToApiService
    {
        private readonly ILogger<SendToApiService> _log;

        public SendToApiService(ILogger<SendToApiService> log)
        {
            _log = log;
        }
        public async Task SendtoApi<T>(T databla, string url, string path)
        {
            Stopwatch sw = new Stopwatch();
            sw.Start();
            var nameOfObjectType = typeof(T).Name;
            var data = JsonConvert.SerializeObject(databla);

          

            //await File.AppendAllTextAsync($"{nameOfObjectType}.json", data);



            var paramss = new Dictionary<string, string>();

            paramss.Add("data", data);

            using (HttpClient client = new HttpClient())
            {
                client.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

                HttpResponseMessage response = await client.PostAsync($"{url}\\{path}", new FormUrlEncodedContent(paramss));
                sw.Stop();
                //var responseContent = await response.Content.ReadAsStringAsync();
                //_log.LogInformation(responseContent);
                _log.LogInformation("Status Code : {@statusCode} Time : {@time}",response.StatusCode.ToString(),sw.ElapsedMilliseconds);
            }
        }
    }
}
