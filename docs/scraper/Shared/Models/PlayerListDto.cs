using System.Collections.Generic;

namespace Shared.Models
{
    public class PlayerListDto
    {
        public string Period { get; set; }
        public List<Club> Club { get; set; } = new List<Club>();
    }
}
