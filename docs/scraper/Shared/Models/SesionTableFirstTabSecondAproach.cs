using Newtonsoft.Json;
using System.Collections.Generic;

namespace Shared.Models
{
    public class SesionTableFirstTabSecondAproach
    {
        [JsonProperty("Round")]
        public string Title { get; set; }

        public string Date { get; set; }
        public string Organiser { get; set; }
        public List<SesionTableFirstTabResultsBase> Details { get; set; }
    }
}
