using System.Collections.Generic;

namespace Shared.Models
{
    public class Club
    {
        public string ClubName { get; set; }
        public string Period { get; set; }
        public List<PlayerListTable> Players { get; set; } = new List<PlayerListTable>();
    }
}
