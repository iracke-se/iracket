namespace Shared.Models
{
    public class LiveCenter
    {
        public string Period { get; set; }

        //public List<Devision> Devisions { get; set; } = new List<Devision>();
        public Devision Devision { get; set; } = new Devision();
    }
}
