using Newtonsoft.Json;
using System.Collections.Generic;

namespace Shared.Models
{
    public class TeamDetails
    {
        [JsonProperty("Name")]
        public string Name { get; set; }

        [JsonProperty("Score")]
        public string Score { get; set; }

        public List<Mathc> Mathces { get; set; } = new List<Mathc>();
    }
}
