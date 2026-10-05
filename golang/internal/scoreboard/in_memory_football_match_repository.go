package scoreboard

import "slices"

// InMemoryFootballMatchRepository keeps the matches in a slice, so they are
// lost when the program ends.
type InMemoryFootballMatchRepository struct {
	matches []*FootballMatch
}

func NewInMemoryFootballMatchRepository() *InMemoryFootballMatchRepository {
	return &InMemoryFootballMatchRepository{}
}

func (r *InMemoryFootballMatchRepository) Save(match *FootballMatch) {
	if i := r.index(match.MatchID()); i >= 0 {
		r.matches[i] = match
		return
	}

	r.matches = append(r.matches, match)
}

func (r *InMemoryFootballMatchRepository) Remove(matchID UUID) {
	if i := r.index(matchID); i >= 0 {
		r.matches = slices.Delete(r.matches, i, i+1)
	}
}

func (r *InMemoryFootballMatchRepository) Find(matchID UUID) (*FootballMatch, bool) {
	if i := r.index(matchID); i >= 0 {
		return r.matches[i], true
	}

	return nil, false
}

// All returns a new slice, so the caller can sort or change it freely.
func (r *InMemoryFootballMatchRepository) All() []*FootballMatch {
	return slices.Clone(r.matches)
}

// index returns the position of the match with the given id, or -1.
func (r *InMemoryFootballMatchRepository) index(matchID UUID) int {
	return slices.IndexFunc(r.matches, func(match *FootballMatch) bool {
		return match.MatchID() == matchID
	})
}
