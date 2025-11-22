using Newtonsoft.Json;

namespace Shared.Models
{
    public class SesionPartsBase
    {
        [JsonProperty("Label")]
        public string Label { get; set; }

        [JsonProperty("Link")]
        public string Link { get; set; }
    }
}
