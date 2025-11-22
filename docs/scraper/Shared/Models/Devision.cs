using System.Collections.Generic;

namespace Shared.Models
{
    public class Devision
    {
        public string DevisionName { get; set; }
        public List<Team> DevisionTeams { get; set; } = new List<Team>();
    }
}
