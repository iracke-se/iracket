using Newtonsoft.Json;
using System.Collections.Generic;

namespace Shared.Models
{
    public class GameSets
    {
        [JsonProperty("Id")]
        public string Id { get; set; }

        [JsonProperty("setNumber")]
        public string Part { get; set; }

        [JsonProperty("Score")]
        public string Score { get; set; }

        public List<SetDetails> SetDetails { get; set; } = new List<SetDetails>();
    }
}
