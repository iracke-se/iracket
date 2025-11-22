using System.Collections.Generic;

namespace Shared.Models
{
    public class SesionTableFirstTabResultsBase
    {
        public string DateFromDetails { get; set; }
        public string Link { get; set; }
        public string Player1 { get; set; }
        public string Player2 { get; set; }
        public string Score { get; set; }
        public List<SesionFirtTabDetails> Details { get; set; } = new List<SesionFirtTabDetails>();
    }
}
