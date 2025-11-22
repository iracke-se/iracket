using System.Collections.Generic;

namespace Shared.Models
{
    public class Session
    {
        public string Name { get; set; }
        public List<SesionTableSecondTab> MatchStatisticPerTeam { get; set; } = new List<SesionTableSecondTab>();
        public List<SesionMatchStatisticPerPlayer> MatchStatisticPerPlayer { get; set; } = new List<SesionMatchStatisticPerPlayer>();
        public List<SesionTableFirstTab> MatcherFirstAproach { get; set; } = new List<SesionTableFirstTab>();
        public List<SesionTableFirstTabSecondAproach> MatcherSecondAproach { get; set; } = new List<SesionTableFirstTabSecondAproach>();
        public List<SesionTableData> SesionTableDataFirstAproach { get; set; } = new List<SesionTableData>();
        public List<SesionTableDataSecondAproach> SesionTableDataSecondAproach { get; set; } = new List<SesionTableDataSecondAproach>();
    }
}
