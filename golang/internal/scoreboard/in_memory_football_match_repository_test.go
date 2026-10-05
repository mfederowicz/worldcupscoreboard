package scoreboard

import (
	"slices"
	"testing"
)

// The compiler checks here that the in-memory repository is a FootballMatchRepository.
var _ FootballMatchRepository = (*InMemoryFootballMatchRepository)(nil)

func unknownID(t *testing.T) UUID {
	t.Helper()

	matchID, err := UUIDFromString("00000000-0000-4000-8000-000000000000")
	if err != nil {
		t.Fatalf("UUIDFromString() error = %v", err)
	}

	return matchID
}

func assertMatches(t *testing.T, got []*FootballMatch, want ...*FootballMatch) {
	t.Helper()

	if !slices.Equal(got, want) {
		t.Errorf("matches = %v, want %v", got, want)
	}
}

func TestNewRepositoryIsEmpty(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()

	assertMatches(t, repository.All())
}

func TestSavedMatchIsFoundByID(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()
	match := newMatch(t, "Mexico", "Canada")

	repository.Save(match)

	if found, ok := repository.Find(match.MatchID()); !ok || found != match {
		t.Errorf("Find() = %v, %v, want the saved match", found, ok)
	}
}

func TestUnknownIDIsNotFound(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()

	if found, ok := repository.Find(unknownID(t)); ok || found != nil {
		t.Errorf("Find() = %v, %v, want nil, false", found, ok)
	}
}

func TestAllReturnsEverySavedMatch(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()
	first := newMatch(t, "Mexico", "Canada")
	second := newMatch(t, "Spain", "Brazil")

	repository.Save(first)
	repository.Save(second)

	assertMatches(t, repository.All(), first, second)
}

func TestSavingSameMatchTwiceDoesNotDuplicateIt(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()
	match := newMatch(t, "Mexico", "Canada")

	repository.Save(match)
	repository.Save(match)

	assertMatches(t, repository.All(), match)
}

func TestRemovedMatchIsNoLongerFound(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()
	removed := newMatch(t, "Mexico", "Canada")
	kept := newMatch(t, "Spain", "Brazil")
	repository.Save(removed)
	repository.Save(kept)

	repository.Remove(removed.MatchID())

	if _, ok := repository.Find(removed.MatchID()); ok {
		t.Error("Find() found the removed match")
	}

	assertMatches(t, repository.All(), kept)
}

func TestRemovingUnknownIDDoesNothing(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()
	match := newMatch(t, "Mexico", "Canada")
	repository.Save(match)

	repository.Remove(unknownID(t))

	assertMatches(t, repository.All(), match)
}

func TestChangingResultOfAllDoesNotChangeRepository(t *testing.T) {
	repository := NewInMemoryFootballMatchRepository()
	first := newMatch(t, "Mexico", "Canada")
	second := newMatch(t, "Spain", "Brazil")
	repository.Save(first)
	repository.Save(second)

	slices.Reverse(repository.All())

	assertMatches(t, repository.All(), first, second)
}
