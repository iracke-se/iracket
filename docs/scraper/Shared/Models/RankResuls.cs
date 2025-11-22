using System.Collections.Generic;

namespace Shared.Models
{
    public class RankResuls
    {
        public int Id { get; set; }
        public string Investment { get; set; }
        public string Name { get; set; }
        public string Club { get; set; }
        public string Born { get; set; }
        public string Point { get; set; }
        public string NotCleardId { get; set; }
        public RankDetails RankDetails { get; set; } = new RankDetails();
    }

   
    public class RankDetails
    {
        public string Licenc { get; set; }
        public List<RankDetailsPoints> Details { get; set; } = new List<RankDetailsPoints>();
    }


    public class RankDetailsPoints
    {
        public string Date { get; set; }
        public string Point { get; set; }
        public string Location { get; set; }
        public string PointDifference { get; set; }
        public string PointId { get; set; }
        public RankPointsDetails PointsDetails { get; set; } = new RankPointsDetails();
    }

    public class RankPointsDetails
    {
        public string ExPoints { get; set; }
        public string Adjustment { get; set; }

        public string ExPointsAdjusted { get; set; }
        public string MatchPoints { get; set; }
        public string Total { get; set; }
        public string Inactive { get; set; }
        public List<RankPointsMatchDetails> MathcDetails { get; set; } = new List<RankPointsMatchDetails>();
    }

    public class RankPointsMatchDetails
    {
        public string Status { get; set; }
        public string Name { get; set; }
        public string Place { get; set; }
        public string MatchPoint { get; set; }
        public string Date { get; set; }
    }
}
