using System.Collections.Generic;

namespace Shared.Models
{
    public class LiveCenterV2
    {
        public string Period { get; set; }
        public string Devision { get; set; }

        //public List<Devision> Devisions { get; set; } = new List<Devision>();
        public List<TeamDetails> Details { get; set; } = new List<TeamDetails>();
    }
}
