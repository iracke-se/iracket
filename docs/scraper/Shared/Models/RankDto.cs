using Shared.Enums;
using System;
using System.Collections.Generic;
using System.Text;

namespace Shared.Models
{
    public class RankDto
    {
        public string Period { get; set; }
        public string Devision { get; set; }
        public Gender Gender { get; set; }
        public List<RankResuls> Data { get; set; } = new List<RankResuls>();
    }
}
