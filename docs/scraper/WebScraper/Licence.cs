using Microsoft.AspNetCore.Http;
using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace WebScraper
{
    public class Licence
    {
        private readonly RequestDelegate _next;

        public Licence(RequestDelegate next)
        {
            _next = next;
        }

        public async Task InvokeAsync(HttpContext context)
        {
           

            // Call the next delegate/middleware in the pipeline
            await _next(context);
        }
    }
}
