using System.Collections.Generic;

namespace Shared.Models
{
    public class DevisionTeams
    {
        public string Name { get; set; }
        public string Score { get; set; }
        public List<DevisionGames> DevisionGames { get; set; }
    }
}
