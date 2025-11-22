using Newtonsoft.Json;
using System.Collections.Generic;

namespace Shared.Models
{
    public class Seasons
    {
        [JsonProperty("Title")]
        public string Title { get; set; }

        [JsonProperty("Parts")]
        public List<SessionPart> SesionParts { get; set; }
    }
}
