using System.Collections.Generic;

namespace Shared.Models
{
    public class SesionTableSecondTab
    {
        public string Name { get; set; }
        public string FinalResult { get; set; }
        public List<SesionTableSecondTabDetails> Details { get; set; }
    }
}
