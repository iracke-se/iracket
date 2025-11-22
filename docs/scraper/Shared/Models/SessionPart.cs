using Newtonsoft.Json;
using System.Collections.Generic;

namespace Shared.Models
{
    public class SessionPart : SesionPartsBase
    {
        [JsonProperty("Nested")]
        public List<SessionPartNested> SessionPartNested { get; set; }
    }
}
