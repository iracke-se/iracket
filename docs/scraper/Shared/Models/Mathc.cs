using Newtonsoft.Json;
using System.Collections.Generic;

namespace Shared.Models
{
    public class Mathc
    {
        [JsonProperty("Id")]
        public string Id { get; set; }

        [JsonProperty("Part")]
        public string Part { get; set; }

        [JsonProperty("Plauer1")]
        public string Plauer1 { get; set; }

        [JsonProperty("Plauer2")]
        public string Plauer2 { get; set; }

        [JsonProperty("Score")]
        public string Score { get; set; }

        public List<GameSets> GameSets { get; set; } = new List<GameSets>();
    }
}
