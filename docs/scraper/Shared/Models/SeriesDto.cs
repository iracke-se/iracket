using System.Collections.Generic;

namespace Shared.Models
{
    public class SeriesDto
    {
        public string SeriesName { get; set; }
        public string Period { get; set; }
        public List<Session> Session { get; set; } = new List<Session>();
    }
}
