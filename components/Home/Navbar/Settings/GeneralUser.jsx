import React, { useState } from "react";

const GenealUser = () => {
  const [query, setQuery] = useState("");
  const [filteredData, setFilteredData] = useState([]);

  const data = [
    "John",
    "Sarah",
    "Mike",
    "Super Admin",
    "Admin",
    "Finance User",
    "HR User",
    "General User",
    "Productivity User",
  ];

  const handleChange = (e) => {
    const value = e.target.value;
    setQuery(value);

    if (value.length > 0) {
      const results = data.filter((item) =>
        item.toLowerCase().includes(value.toLowerCase())
      );
      setFilteredData(results);
    } else {
      setFilteredData([]);
    }
  };

  return (
    <div className="relative w-72">
      {/* Search Input */}
      <input
        type="text"
        placeholder="Search user/role..."
        value={query}
        onChange={(e)=>handleChange(e)}
        className="w-full p-2 border border-gray-300 rounded-md focus:outline-none"
      />

      {/* Suggestions */}
      {filteredData.length > 0 && (
        <ul className="absolute left-0 right-0 mt-1 bg-white border border-gray-200 rounded-md shadow-md max-h-40 overflow-y-auto z-10">
          {filteredData.map((item, index) => (
            <li
              key={index}
              className="px-4 py-2 hover:bg-gray-100 cursor-pointer"
              onClick={() => {
                setQuery(item);
                setFilteredData([]);
              }}
            >
              {item}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
};

export default GenealUser;
