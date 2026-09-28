const getSelectedFilters = () => {
    const activePill = document.querySelector('.filter-pill.active');
    const marketDropdown = document.querySelector('.market-dropdown');

    return {
        type: activePill?.dataset.filter ?? null,
        market_name: marketDropdown?.value ?? null,
    };
};

const pills = document.querySelectorAll('.filter-pill');
pills.forEach((pill) => {
    pill.onclick = async function () {
        const wasSelected = this.classList.contains('active');
        pills.forEach((item) => {
            item.classList.remove('active');
        });
        if (!wasSelected) {
            this.classList.add('active');
        }
        callFixtureData(getSelectedFilters());
    }
});

document.querySelectorAll('.market-dropdown').forEach((element) => {
    element.addEventListener('change', function () {
        callFixtureData(getSelectedFilters());
    });
});

const callFixtureData = async (value) => {
    try {
        const url = new URL('/football/filter-football',window.location.origin);
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(value),
        });

        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }

        const data = await response.json();
        console.log("data",data);

        document.querySelector('.fixtures-container').innerHTML = data.html;
        console.log(data);

    } catch (error) {
        console.error('Request failed:', error);
    }
};

window.Echo.channel('sports.football.live')
    .listen('.score.updated', async (event) => {
        console.log("eventtt",event);

        try {
            const url = new URL('/football/websocket-football',window.location.origin);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify(event),
            });

            if (!response.ok) {
                throw new Error(`HTTP error: ${response.status}`);
            }

            const data = await response.json();

            document.querySelector('.fixtures-container').innerHTML = data.html;

            console.log('Score updated:', event);
            console.log('Fixture data:', data);

        } catch (error) {
            console.error('Request failed:', error);
        }
    });
