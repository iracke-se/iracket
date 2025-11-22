using System.Collections.Generic;

namespace Shared.Models
{
    public class TransitionsDto
    {
        public string Period { get; set; }
        public List<TransitionsDetailsDto> Data { get; set; } = new List<TransitionsDetailsDto>();
    }
}
