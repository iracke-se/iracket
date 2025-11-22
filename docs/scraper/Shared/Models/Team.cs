using Newtonsoft.Json;

namespace Shared.Models
{
    public class Team
    {
        [JsonProperty("Id")]
        public string Id { get; set; }

        [JsonProperty("values")]
        public TeamDetails TeamDetails { get; set; }
    }
}
