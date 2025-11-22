using Newtonsoft.Json;

namespace Shared.Models
{
    public class SetDetails
    {
        [JsonProperty("Score")]
        public string Score { get; set; }

        [JsonProperty("Serve")]
        public string Serve { get; set; }

        [JsonProperty("Comment")]
        public string Comment { get; set; }
    }
}
